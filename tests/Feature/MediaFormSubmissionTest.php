<?php

namespace Tests\Feature;

use App\Models\MediaForm;
use App\Models\MediaFormAnswer;
use App\Models\MediaFormField;
use App\Models\MediaFormSubmission;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Cache\RateLimiter;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MediaFormSubmissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)), 'cache.default' => 'array']);
        Http::preventStrayRequests();
        Schema::create('users', function (Blueprint $table) { $table->id(); });
        (require database_path('migrations/2026_08_21_120000_create_media_forms_tables.php'))->up();
    }

    private function form(): MediaForm
    {
        return MediaForm::create(['title' => 'Atualização cadastral', 'public_slug' => 'atualizacao-teste',
            'is_active' => true, 'is_accepting_responses' => true, 'requires_identification' => false]);
    }

    public function test_public_registration_update_can_be_submitted_and_confirmation_displayed_without_login(): void
    {
        $this->withoutExceptionHandling();
        $form = $this->form();
        $fields = $form->fields()->createMany([
            ['label' => 'email', 'type' => 'email', 'is_required' => true, 'sort_order' => 0],
            ['label' => 'Data de Nascimento', 'type' => 'date', 'is_required' => true, 'sort_order' => 1],
            ['label' => 'Nome Completo', 'type' => 'text', 'is_required' => true, 'sort_order' => 2],
            ['label' => 'Telefone', 'type' => 'text', 'is_required' => true, 'sort_order' => 3],
        ]);
        $answers = array_combine($fields->modelKeys(), ['pessoa@example.test', '1995-06-15', 'Pessoa de teste', '(61) 99999-0000']);
        $this->get(route('formularios.public.show', $form->public_slug))->assertOk()->assertSee('Enviar resposta');
        $this->post(route('formularios.public.submit', $form->public_slug), ['answers' => $answers])
            ->assertRedirect(route('formularios.public.show', $form->public_slug))->assertSessionHas('form_submitted', true);
        $submission = $form->submissions()->sole();
        $this->assertNull($submission->respondent_name);
        $this->assertCount(4, $submission->answers);
        foreach ($answers as $id => $value) $this->assertDatabaseHas('media_form_answers', ['submission_id' => $submission->id, 'field_id' => $id, 'value' => $value]);
        $this->get(route('formularios.public.show', $form->public_slug))->assertOk()->assertSee('Resposta enviada')->assertDontSee('Enviar resposta');
        $this->assertSame(1, MediaFormSubmission::count());
        Http::assertNothingSent();
    }

    public function test_invalid_fields_return_validation_errors_and_preserve_previous_answers(): void
    {
        $form = $this->form();
        $email = $form->fields()->create(['label' => 'E-mail', 'type' => 'email', 'is_required' => true]);
        $date = $form->fields()->create(['label' => 'Nascimento', 'type' => 'date', 'is_required' => true]);
        $name = $form->fields()->create(['label' => 'Nome', 'type' => 'text', 'is_required' => true]);
        $url = route('formularios.public.submit', $form->public_slug);
        $this->post($url, ['answers' => [$email->id => 'primeiro@example.test', $date->id => '1990-01-01', $name->id => 'Nome anterior']])->assertSessionHasNoErrors();
        $before = DB::table('media_form_answers')->orderBy('id')->get()->toJson();
        $this->postJson($url, ['answers' => [$email->id => 'incorreto', $date->id => '2027-02-30', $name->id => 'Nome válido']])
            ->assertUnprocessable()->assertJsonValidationErrors(['answers.'.$email->id, 'answers.'.$date->id]);
        $this->assertSame($before, DB::table('media_form_answers')->orderBy('id')->get()->toJson());
        $this->assertSame(1, MediaFormSubmission::count());
    }

    public function test_closed_deleted_and_inactive_forms_do_not_accept_submissions(): void
    {
        $form = $this->form();
        $url = route('formularios.public.submit', $form->public_slug);
        $form->update(['is_accepting_responses' => false]);
        $this->post($url)->assertRedirect()->assertSessionHas('error');
        $form->update(['is_active' => false]);
        $this->post($url)->assertNotFound();
        $form->delete();
        $this->post($url)->assertNotFound();
        $this->assertSame(0, MediaFormSubmission::count());
        $this->assertSame(0, MediaFormAnswer::count());
    }

    public function test_file_cache_keeps_its_directories_searchable_and_enforces_the_public_submission_limit(): void
    {
        $directory = storage_path('framework/testing/media-form-cache-'.bin2hex(random_bytes(8)));
        $filesystem = new class extends Filesystem {
            public array $unsafeDirectories = [];

            public function chmod($path, $mode = null)
            {
                if ($mode !== null && is_dir($path) && ($mode & 0111) === 0) {
                    $this->unsafeDirectories[] = $path;
                }
                return parent::chmod($path, $mode);
            }
        };
        $this->app->instance('files', $filesystem);
        config(['cache.default' => 'file', 'cache.limiter' => 'file', 'cache.stores.file.path' => $directory]);
        Cache::forgetDriver('file');
        $this->app->forgetInstance(RateLimiter::class);
        $form = $this->form();
        $field = $form->fields()->create(['label' => 'Nome', 'type' => 'text', 'is_required' => true]);
        $url = route('formularios.public.submit', $form->public_slug);
        $oldMask = umask(0002);
        try {
            for ($i = 0; $i < 10; $i++) {
                $this->post($url, ['answers' => [$field->id => 'Pessoa de teste']])->assertRedirect()->assertSessionHasNoErrors();
            }
            $this->postJson($url, ['answers' => [$field->id => 'Limite atingido']])->assertStatus(429);
            $this->assertSame(10, MediaFormSubmission::count());
            $this->assertSame(10, MediaFormAnswer::count());
            $this->assertTrue(Cache::lock('media-form-regression', 10)->get(fn () => true));
            $this->assertSame([], $filesystem->unsafeDirectories, 'O cache não deve aplicar permissões de arquivo sem execução às pastas.');
        } finally {
            umask($oldMask);
            // Only this test's random cache tree is repaired and removed.
            if (is_dir($directory)) {
                chmod($directory, 0775);
                foreach (glob($directory.'/*', GLOB_ONLYDIR) ?: [] as $parent) {
                    chmod($parent, 0775);
                    foreach (glob($parent.'/*', GLOB_ONLYDIR) ?: [] as $child) chmod($child, 0775);
                }
                $filesystem->deleteDirectory($directory);
            }
        }
    }
}
