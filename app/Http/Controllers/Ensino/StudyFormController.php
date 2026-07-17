<?php

namespace App\Http\Controllers\Ensino;

use App\Http\Controllers\Controller;
use App\Models\Study;
use App\Models\StudyForm;
use App\Models\StudyFormQuestion;
use App\Services\StudyFormTextParser;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudyFormController extends Controller
{
    public function __construct(
        private StudyFormTextParser $parser
    ) {}

    public function index(Study $estudo)
    {
        $this->authorize('view', $estudo);

        $forms = $estudo->forms()
            ->withCount(['questions', 'submissions'])
            ->latest()
            ->get();

        return view('ensino.estudos.formularios.index', [
            'estudo' => $estudo,
            'forms' => $forms,
        ]);
    }

    public function create(Study $estudo)
    {
        $this->authorize('update', $estudo);

        return view('ensino.estudos.formularios.create', [
            'estudo' => $estudo,
        ]);
    }

    public function preview(Request $request, Study $estudo)
    {
        $this->authorize('update', $estudo);

        $validated = $request->validate([
            'source_text' => 'required|string|min:3',
        ]);

        $questions = $this->parser->parse($validated['source_text']);

        return response()->json([
            'questions' => $questions,
            'count' => count($questions),
        ]);
    }

    public function store(Request $request, Study $estudo)
    {
        $this->authorize('update', $estudo);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
            'source_text' => 'nullable|string',
            'questions' => 'nullable|array|min:1',
            'questions.*.prompt' => 'required_with:questions|string|max:5000',
            'questions.*.theme' => 'nullable|string|max:255',
            'questions.*.type' => 'required_with:questions|in:dissertative,multiple_choice,true_false',
            'questions.*.options' => 'nullable|array',
            'questions.*.options_text' => 'nullable|string',
            'questions.*.correct_answer' => 'nullable|string|max:1000',
            'questions.*.is_required' => 'nullable|boolean',
        ]);

        $questions = $request->input('questions', []);
        if ($questions === [] && filled($validated['source_text'] ?? null)) {
            $questions = $this->parser->parse($validated['source_text']);
        }



        if ($questions === []) {
            return back()
                ->withInput()
                ->withErrors(['source_text' => 'Não foi possível identificar perguntas. Cole o texto ou adicione perguntas manualmente.']);
        }

        $isActive = $request->boolean('is_active');

        $form = DB::transaction(function () use ($estudo, $validated, $questions, $isActive) {
            $form = StudyForm::create([
                'study_id' => $estudo->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'public_slug' => StudyForm::generateUniqueSlug($validated['title']),
                'is_active' => $isActive,
            ]);

            $this->syncQuestions($form, $questions);

            return $form;
        });


        return redirect()
            ->route('ensino.estudos.formularios.show', [$estudo, $form])
            ->with('success', 'Formulário criado com sucesso!');
    }

    public function show(Study $estudo, StudyForm $formulario)
    {
        $this->authorize('view', $estudo);
        $this->ensureBelongsToStudy($estudo, $formulario);

        $formulario->load(['questions', 'submissions' => fn ($q) => $q->latest('submitted_at')->limit(50)]);
        $formulario->loadCount('submissions');

        return view('ensino.estudos.formularios.show', [
            'estudo' => $estudo,
            'formulario' => $formulario,
        ]);
    }

    public function edit(Study $estudo, StudyForm $formulario)
    {
        $this->authorize('update', $estudo);
        $this->ensureBelongsToStudy($estudo, $formulario);

        $formulario->load('questions');

        return view('ensino.estudos.formularios.edit', [
            'estudo' => $estudo,
            'formulario' => $formulario,
        ]);
    }

    public function update(Request $request, Study $estudo, StudyForm $formulario)
    {
        $this->authorize('update', $estudo);
        $this->ensureBelongsToStudy($estudo, $formulario);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
            'questions' => 'required|array|min:1',
            'questions.*.prompt' => 'required|string|max:5000',
            'questions.*.theme' => 'nullable|string|max:255',
            'questions.*.type' => 'required|in:dissertative,multiple_choice,true_false',
            'questions.*.options' => 'nullable|array',
            'questions.*.options_text' => 'nullable|string',
            'questions.*.correct_answer' => 'nullable|string|max:1000',
            'questions.*.is_required' => 'nullable|boolean',
        ]);


        $questions = $request->input('questions', []);

        DB::transaction(function () use ($request, $formulario, $validated, $questions) {
            $formulario->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]);

            $formulario->questions()->delete();
            $this->syncQuestions($formulario, $questions);
        });


        return redirect()
            ->route('ensino.estudos.formularios.show', [$estudo, $formulario])
            ->with('success', 'Formulário atualizado com sucesso!');
    }

    public function destroy(Study $estudo, StudyForm $formulario)
    {
        $this->authorize('update', $estudo);
        $this->ensureBelongsToStudy($estudo, $formulario);

        $formulario->delete();

        return redirect()
            ->route('ensino.estudos.formularios.index', $estudo)
            ->with('success', 'Formulário removido com sucesso!');
    }

    public function submissions(Study $estudo, StudyForm $formulario)
    {
        $this->authorize('view', $estudo);
        $this->ensureBelongsToStudy($estudo, $formulario);

        $submissions = $formulario->submissions()
            ->with(['answers.question'])
            ->latest('submitted_at')
            ->paginate(20);

        return view('ensino.estudos.formularios.submissions', [
            'estudo' => $estudo,
            'formulario' => $formulario,
            'submissions' => $submissions,
        ]);
    }

    public function showSubmission(Study $estudo, StudyForm $formulario, int $submission)
    {
        $this->authorize('view', $estudo);
        $this->ensureBelongsToStudy($estudo, $formulario);

        $submissionModel = $formulario->submissions()
            ->with(['answers.question'])
            ->findOrFail($submission);

        return view('ensino.estudos.formularios.submission-show', [
            'estudo' => $estudo,
            'formulario' => $formulario,
            'submission' => $submissionModel,
        ]);
    }

    public function pdf(Study $estudo, StudyForm $formulario)
    {
        $this->authorize('view', $estudo);
        $this->ensureBelongsToStudy($estudo, $formulario);

        $formulario->load(['study', 'questions']);

        $sections = [];
        $currentKey = null;
        foreach ($formulario->questions as $question) {
            $key = ($question->theme ?: '').'|'.$question->type;
            if ($key !== $currentKey) {
                $sections[] = [
                    'theme' => $question->theme,
                    'type_label' => $question->typeLabel(),
                    'questions' => collect(),
                ];
                $currentKey = $key;
            }
            $sections[array_key_last($sections)]['questions']->push($question);
        }

        $pdf = Pdf::loadView('ensino.estudos.formularios.pdf', [
            'estudo' => $estudo,
            'formulario' => $formulario,
            'sections' => $sections,
            'withAnswers' => request()->boolean('gabarito'),
        ])->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'DejaVu Sans');


        $suffix = request()->boolean('gabarito') ? '-gabarito' : '';
        $filename = 'questionario-'.Str::slug($formulario->title).$suffix.'.pdf';

        return $pdf->download($filename);
    }

    private function ensureBelongsToStudy(Study $estudo, StudyForm $formulario): void
    {
        if ((int) $formulario->study_id !== (int) $estudo->id) {
            abort(404);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     */
    private function syncQuestions(StudyForm $form, array $questions): void
    {
        foreach (array_values($questions) as $index => $question) {
            $type = $question['type'] ?? StudyFormQuestion::TYPE_DISSERTATIVE;
            $options = $question['options'] ?? null;

            if ($type === StudyFormQuestion::TYPE_TRUE_FALSE) {
                $options = [
                    ['key' => 'true', 'text' => 'Verdadeiro'],
                    ['key' => 'false', 'text' => 'Falso'],
                ];
            }

            if ($type === StudyFormQuestion::TYPE_MULTIPLE_CHOICE) {
                $options = $this->normalizeMultipleChoiceOptions($options, $question);
            }

            if ($type === StudyFormQuestion::TYPE_DISSERTATIVE) {
                $options = null;
            }

            $theme = trim((string) ($question['theme'] ?? ''));

            StudyFormQuestion::create([
                'study_form_id' => $form->id,
                'prompt' => $question['prompt'],
                'theme' => $theme !== '' ? $theme : null,
                'type' => $type,
                'options' => $options,
                'correct_answer' => $question['correct_answer'] ?? null,
                'is_required' => filter_var($question['is_required'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'sort_order' => $question['sort_order'] ?? $index,
            ]);
        }
    }



    /**
     * @param  mixed  $options
     * @param  array<string, mixed>  $question
     * @return array<int, array{key: string, text: string}>
     */
    private function normalizeMultipleChoiceOptions(mixed $options, array $question): array
    {
        if (is_string($options)) {
            $options = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $options) ?: [])));
        }

        if (! is_array($options) || $options === []) {
            // Aceita options_text do formulário HTML
            if (! empty($question['options_text']) && is_string($question['options_text'])) {
                $options = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $question['options_text']) ?: [])));
            }
        }

        $normalized = [];
        foreach (array_values($options ?? []) as $i => $opt) {
            if (is_array($opt)) {
                $key = (string) ($opt['key'] ?? chr(97 + $i));
                $text = trim((string) ($opt['text'] ?? ''));
            } else {
                $key = chr(97 + $i);
                $text = trim((string) $opt);
            }

            if ($text === '') {
                continue;
            }

            $normalized[] = ['key' => $key, 'text' => $text];
        }

        return $normalized;
    }
}
