<?php

namespace Tests\Feature;

use App\Models\Study;
use App\Models\StudyForm;
use App\Models\StudyFormQuestion;
use App\Models\StudyFormSubmission;
use App\Services\IbadegBookExcerpts;
use App\Services\IbadegCourseImporter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IbadegCourseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            '2026_01_10_200207_create_studies_table.php',
            '2026_07_17_160000_create_study_forms_tables.php',
            '2026_07_17_164500_add_theme_to_study_form_questions_table.php',
            '2026_11_09_120000_add_course_materials_to_study_forms.php',
            '2026_11_09_120100_expand_study_question_reference_answers.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
    }

    private function form(): StudyForm
    {
        $study = Study::create(['name' => 'Curso de teste']);
        $form = $study->forms()->create([
            'title' => 'Prova', 'public_slug' => 'prova-teste', 'is_active' => true,
            'material_path' => 'studies/ibadeg/books/01.pdf',
        ]);
        $form->questions()->createMany([
            ['prompt' => 'Item objetivo', 'type' => 'multiple_choice', 'sort_order' => 0,
                'options' => [['key' => 'C', 'text' => 'Certo'], ['key' => 'E', 'text' => 'Errado']],
                'correct_answer' => 'C', 'is_required' => true],
            ['prompt' => 'Item verdadeiro ou falso', 'type' => 'true_false', 'sort_order' => 1,
                'correct_answer' => 'true', 'is_required' => true, 'review_required' => true],
            ['prompt' => 'Questão aberta', 'type' => 'dissertative', 'sort_order' => 2,
                'correct_answer' => 'Referência reservada ao resultado', 'is_required' => true],
        ]);

        return $form->load('questions');
    }

    public function test_imports_the_complete_course_and_reimport_preserves_questions_and_attempts(): void
    {
        Storage::fake('public');
        // Fonte temporária sob storage; nenhum arquivo do usuário é alterado.
        $source = storage_path('framework/testing/ibadeg-source-'.bin2hex(random_bytes(5)));
        File::ensureDirectoryExists($source.'/SITE PROVAS');
        File::ensureDirectoryExists($source.'/LIVROS');
        File::ensureDirectoryExists($source.'/PROVAS');
        $json = file_get_contents(base_path('tests/Fixtures/ibadeg-exams.json'));
        file_put_contents($source.'/SITE PROVAS/index.html', '<script>const EXAMS = '.$json.';</script>');
        foreach (range(1, 21) as $n) {
            foreach (['LIVROS', 'PROVAS'] as $folder) {
                file_put_contents($source.'/'.$folder.'/'.sprintf('%02d', $n).' - Material.pdf', '%PDF-1.4 test');
            }
        }
        file_put_contents($source.'/PROVAS/Todas.pdf', '%PDF-1.4 test');

        try {
            $importer = app(IbadegCourseImporter::class);
            $study = $importer->import($source);
            $this->assertSame(21, $study->forms()->count());
            $this->assertSame(761, StudyFormQuestion::count());
            $this->assertSame(43, count(Storage::disk('public')->allFiles('studies/ibadeg')));
            $this->assertGreaterThan(0, StudyFormQuestion::where('review_required', true)->count());
            $this->assertSame(62, StudyFormQuestion::where('type', 'dissertative')->count());
            foreach ($study->forms as $form) {
                Storage::disk('public')->assertExists($form->material_path);
                Storage::disk('public')->assertExists($form->exam_pdf_path);
            }

            // Os 235 enunciados aparecem uma vez, incluindo C/E, associação e lacunas.
            $sourceExams = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            foreach ($study->forms as $index => $form) {
                $page = $this->get($form->publicUrl())->assertOk();
                $document = new \DOMDocument;
                @$document->loadHTML('<?xml encoding="UTF-8">'.$page->getContent());
                $headings = (new \DOMXPath($document))->query('//h3[@class="ib-instruction"]');
                $actual = array_map(fn ($node) => $node->textContent, iterator_to_array($headings));
                $expected = array_map(fn ($question) => $question['n'].') '.$question['instruction'], $sourceExams[$index]['questions']);
                $this->assertSame($expected, $actual, $form->title);
                $page->assertSee('Corrigir Prova')->assertDontSee('data-step=', false);
            }

            $first = $study->forms->first();
            $question = $first->questions->first();
            $submission = $first->submissions()->create(['respondent_name' => 'Aluno', 'submitted_at' => now()]);
            $answer = $submission->answers()->create(['question_id' => $question->id, 'selected_option' => 'C', 'is_correct' => true]);
            $importer->import($source);
            $this->assertSame(1, Study::count());
            $this->assertSame(21, StudyForm::count());
            $this->assertSame(761, StudyFormQuestion::count());
            $this->assertSame($question->id, $answer->fresh()->question->id);
            $this->assertSame(1, StudyFormSubmission::count());

            $html = view('ensino.estudos.partials.course', [
                'courseForms' => $study->forms()->withCount('questions')->orderBy('title')->get(),
            ])->render();
            $this->assertSame(21, substr_count($html, 'Fazer prova'));
            $this->assertStringContainsString('/estudo-formulario/ibadeg-21', $html);

            // Falta de um material deve interromper antes de criar o curso.
            unlink($source.'/LIVROS/21 - Material.pdf');
            try {
                $importer->import($source);
                $this->fail('A importação deveria exigir todos os PDFs.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('21 em LIVROS', $exception->getMessage());
            }
            $this->assertSame(761, StudyFormQuestion::count());
        } finally {
            File::deleteDirectory($source);
        }
    }

    public function test_submission_computes_grade_and_shows_private_corrections_and_reference(): void
    {
        $form = $this->form();
        [$choice, $tf, $open] = $form->questions->all();
        $this->get($form->publicUrl())->assertOk()->assertDontSee('Referência reservada ao resultado');

        $this->post(route('study.forms.public.submit', $form->public_slug), [
            'respondent_name' => 'Aluno <script>teste</script>',
            'score' => 100,
            'answers' => [$choice->id => 'C', $tf->id => 'false', $open->id => 'Minha resposta aberta'],
        ])->assertRedirect(route('study.forms.public.result', $form->public_slug));

        $submission = $form->submissions()->firstOrFail();
        $this->assertEquals(1, $submission->score);
        $this->assertEquals(2, $submission->max_score);
        $this->assertNull($submission->answers()->where('question_id', $open->id)->first()->is_correct);
        $answer = $submission->answers()->where('question_id', $tf->id)->first();
        $resultUrl = route('study.forms.public.result', $form->public_slug);
        $this->get($resultUrl)->assertOk()->assertSee('5,00 / 10')
            ->assertSee('1 de 2 itens objetivos corretos')
            ->assertSee('href="#resposta-correta-'.$answer->id.'"', false)
            ->assertSee('Ver resposta certa')->assertSee('Verdadeiro')
            ->assertSee('Minha resposta aberta')->assertSee('Referência reservada ao resultado')
            ->assertSee('/storage/studies/ibadeg/books/01.pdf')
            ->assertSee('Gabarito original pendente de revisão')
            ->assertDontSee('<script>teste</script>', false);
        $this->get($resultUrl)->assertOk(); // Atualizar não perde a nota.
        $this->flushSession();
        $this->get($resultUrl)->assertNotFound();
    }

    public function test_validation_rejects_missing_answers_and_invalid_options(): void
    {
        $form = $this->form();
        $this->post(route('study.forms.public.submit', $form->public_slug), [
            'respondent_name' => 'Aluno', 'answers' => [$form->questions->first()->id => 'FORJADA'],
        ])->assertSessionHasErrors('answers.'.$form->questions->first()->id);
        $this->assertSame(0, StudyFormSubmission::count());
    }

    public function test_inactive_forms_and_deleted_courses_do_not_accept_submissions(): void
    {
        $form = $this->form();
        $form->update(['is_active' => false]);
        $this->get($form->publicUrl())->assertNotFound();
        $this->post(route('study.forms.public.submit', $form->public_slug))->assertNotFound();
        $form->update(['is_active' => true]);
        $form->study->delete();
        $this->get($form->publicUrl())->assertNotFound();
        $this->post(route('study.forms.public.submit', $form->public_slug))->assertNotFound();
    }

    public function test_an_open_only_form_has_reference_without_a_numeric_grade(): void
    {
        $form = $this->form();
        $form->questions()->where('type', '!=', 'dissertative')->delete();
        $open = $form->questions()->first();
        $this->post(route('study.forms.public.submit', $form->public_slug), [
            'respondent_name' => 'Aluno', 'answers' => [$open->id => 'Resposta'],
        ])->assertRedirect();
        $this->get(route('study.forms.public.result', $form->public_slug))->assertOk()
            ->assertSee('não possui itens com correção automática')
            ->assertSee('Referência reservada ao resultado')->assertDontSee('Nota:');
    }

    public function test_editing_imported_options_keeps_their_answer_keys_and_review_flag(): void
    {
        $form = $this->form();
        $user = new \App\Models\User(['name' => 'Professor', 'is_admin' => true]);
        $user->id = 1;
        $this->actingAs($user)->put(route('ensino.estudos.formularios.update', [$form->study, $form]), [
            'title' => 'Prova revisada', 'is_active' => 1,
            'questions' => [[
                'prompt' => 'Item revisado', 'type' => 'multiple_choice',
                'options_text' => "C::Certo\nE::Errado", 'correct_answer' => 'C',
                'is_required' => true, 'review_required' => true,
            ]],
        ])->assertRedirect();
        $question = $form->questions()->first();
        $this->assertSame('C', $question->correct_answer);
        $this->assertSame(['C', 'E'], array_column($question->options, 'key'));
        $this->assertTrue($question->review_required);
    }

    public function test_ibadeg_result_groups_items_and_reveals_corrections_with_verify_buttons(): void
    {
        $source = json_decode(file_get_contents(base_path('tests/Fixtures/ibadeg-exams.json')), true);
        $study = Study::create(['name' => 'IBADEG']);
        $form = $study->forms()->create([
            'title' => 'Introdução Bíblica', 'public_slug' => 'ibadeg-test', 'import_key' => 'ibadeg-01',
            'is_active' => true, 'material_path' => 'studies/ibadeg/books/01.pdf',
        ]);
        $form->questions()->createMany(app(IbadegCourseImporter::class)->questions($source[0]));
        $questions = $form->questions()->get();
        $answers = $questions->pluck('correct_answer', 'id')->all();
        $first = $questions->first();
        $answers[$first->id] = 'C'; // O gabarito do primeiro item é E.
        $this->post(route('study.forms.public.submit', $form->public_slug), [
            'respondent_name' => 'Aluno', 'answers' => $answers,
        ])->assertRedirect(route('study.forms.public.result', $form->public_slug));
        $page = $this->get(route('study.forms.public.result', $form->public_slug))
            ->assertOk()->assertSee('9,75 / 10')->assertSee('39 de 40 itens objetivos corretos')
            ->assertSee('Verificar')->assertSee('Nos estudos superiores')
            ->assertSee('conduzir para dentro, porque')->assertSee('página 7 do PDF')
            ->assertSee('id="verification-'.$first->id.'" hidden', false)
            ->assertSee('aria-controls="verification-'.$first->id.'" aria-expanded="false"', false)
            ->assertDontSee('Consultar apostila da disciplina')
            ->assertDontSee('Resposta correta:');
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$page->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertSame(10, $xpath->query('//h3[@class="ib-instruction"]')->length);
        $this->assertSame(40, $xpath->query('//input[@type="radio" and @checked and @disabled]')->length);
        $this->assertSame(1, $form->submissions()->count());
    }

    public function test_book_excerpts_are_only_shown_after_submission_and_do_not_survive_question_changes(): void
    {
        $source = json_decode(file_get_contents(base_path('tests/Fixtures/ibadeg-exams.json')), true);
        $form = $this->form();
        $form->update(['import_key' => 'ibadeg-01']);
        $form->questions()->delete();
        $first = $form->questions()->create(app(IbadegCourseImporter::class)->questions($source[0])[0]);
        $form->load('questions');
        $excerpts = app(IbadegBookExcerpts::class)->forForm($form);
        $this->assertStringContainsString('conduzir para dentro', $excerpts[$first->id]['text']);
        $this->assertSame('7', $excerpts[$first->id]['page']);
        $this->get($form->publicUrl())->assertOk()->assertDontSee('Nos estudos superiores');

        $first->update(['prompt' => 'Enunciado alterado pelo professor']);
        $form->load('questions');
        $this->assertSame([], app(IbadegBookExcerpts::class)->forForm($form));
        $first->update(['prompt' => app(IbadegCourseImporter::class)->questions($source[0])[0]['prompt'], 'correct_answer' => 'C']);
        $form->load('questions');
        $this->assertSame([], app(IbadegBookExcerpts::class)->forForm($form));

        $this->post(route('study.forms.public.submit', $form->public_slug), [
            'respondent_name' => 'Aluno', 'answers' => [$first->id => 'E'],
        ])->assertRedirect();
        $this->get(route('study.forms.public.result', $form->public_slug))->assertOk()
            ->assertSee('Ainda não há um trecho do livro associado com segurança a este item.')
            ->assertDontSee('Nos estudos superiores')->assertDontSee('Resposta correta:');
    }
}
