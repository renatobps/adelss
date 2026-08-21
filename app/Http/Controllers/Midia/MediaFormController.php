<?php

namespace App\Http\Controllers\Midia;

use App\Http\Controllers\Controller;
use App\Models\MediaForm;
use App\Models\MediaFormField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MediaFormController extends Controller
{
    public function index(Request $request)
    {
        $busca = trim((string) $request->input('q', ''));
        $situacao = $request->input('situacao', '');

        $query = MediaForm::query()
            ->withCount(['submissions', 'fields'])
            ->latest();

        if ($busca !== '') {
            $query->where(function ($q) use ($busca) {
                $q->where('title', 'like', "%{$busca}%")
                    ->orWhere('description', 'like', "%{$busca}%");
            });
        }

        if ($situacao === 'abertos') {
            $query->open();
        } elseif ($situacao === 'encerrados') {
            $query->where(function ($q) {
                $q->where('is_active', false)->orWhere('is_accepting_responses', false);
            });
        }

        return view('midia.formularios.index', [
            'forms' => $query->paginate(15)->withQueryString(),
            'filters' => ['q' => $busca, 'situacao' => $situacao],
        ]);
    }

    public function create()
    {
        return view('midia.formularios.form', [
            'form' => new MediaForm(['is_active' => true, 'is_accepting_responses' => true, 'requires_identification' => true]),
            'fields' => collect(),
        ]);
    }

    public function store(Request $request)
    {
        $dados = $this->validated($request);

        $form = DB::transaction(function () use ($dados) {
            $form = MediaForm::create([
                'title' => $dados['title'],
                'description' => $dados['description'] ?? null,
                'public_slug' => MediaForm::generateUniqueSlug($dados['title']),
                'is_active' => (bool) ($dados['is_active'] ?? false),
                'is_accepting_responses' => (bool) ($dados['is_accepting_responses'] ?? false),
                'requires_identification' => (bool) ($dados['requires_identification'] ?? false),
                'success_message' => $dados['success_message'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $this->syncFields($form, $dados['fields']);

            return $form;
        });

        return redirect()
            ->route('midia.formularios.edit', $form)
            ->with('success', 'Formulário criado. O link público já pode ser compartilhado.');
    }

    public function edit(MediaForm $mediaForm)
    {
        return view('midia.formularios.form', [
            'form' => $mediaForm,
            'fields' => $mediaForm->fields,
        ]);
    }

    public function update(Request $request, MediaForm $mediaForm)
    {
        $dados = $this->validated($request);

        DB::transaction(function () use ($dados, $mediaForm) {
            $mediaForm->update([
                'title' => $dados['title'],
                'description' => $dados['description'] ?? null,
                'is_active' => (bool) ($dados['is_active'] ?? false),
                'is_accepting_responses' => (bool) ($dados['is_accepting_responses'] ?? false),
                'requires_identification' => (bool) ($dados['requires_identification'] ?? false),
                'success_message' => $dados['success_message'] ?? null,
            ]);

            $this->syncFields($mediaForm, $dados['fields']);
        });

        return redirect()
            ->route('midia.formularios.edit', $mediaForm)
            ->with('success', 'Formulário atualizado.');
    }

    public function destroy(MediaForm $mediaForm)
    {
        $mediaForm->delete();

        return redirect()
            ->route('midia.formularios.index')
            ->with('success', 'Formulário removido. As respostas já coletadas continuam guardadas.');
    }

    private function validated(Request $request): array
    {
        $dados = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'success_message' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
            'is_accepting_responses' => 'nullable|boolean',
            'requires_identification' => 'nullable|boolean',
            'fields' => 'required|array|min:1',
            'fields.*.id' => 'nullable|integer',
            'fields.*.label' => 'required|string|max:255',
            'fields.*.help_text' => 'nullable|string|max:255',
            'fields.*.type' => ['required', Rule::in(array_keys(MediaFormField::TYPES))],
            'fields.*.options_text' => 'nullable|string|max:2000',
            'fields.*.is_required' => 'nullable|boolean',
        ], [
            'fields.required' => 'Adicione pelo menos um campo ao formulário.',
            'fields.*.label.required' => 'Todo campo precisa de um título.',
        ]);

        // Lista de selecao, escolha unica e caixas de selecao nao funcionam sem
        // opcoes, e uma opcao unica nao da escolha nenhuma.
        foreach (array_values($dados['fields']) as $indice => $campo) {
            if (!in_array($campo['type'], MediaFormField::TYPES_WITH_OPTIONS, true)) {
                continue;
            }

            if (count($this->parseOptions($campo['options_text'] ?? '')) < 2) {
                throw ValidationException::withMessages([
                    "fields.{$indice}.options_text" => 'Informe pelo menos duas opções, uma por linha.',
                ]);
            }
        }

        return $dados;
    }

    private function syncFields(MediaForm $form, array $fields): void
    {
        $mantidos = [];

        foreach (array_values($fields) as $indice => $campo) {
            $temOpcoes = in_array($campo['type'], MediaFormField::TYPES_WITH_OPTIONS, true);

            $atributos = [
                'label' => trim($campo['label']),
                'help_text' => filled($campo['help_text'] ?? null) ? trim($campo['help_text']) : null,
                'type' => $campo['type'],
                'options' => $temOpcoes ? $this->parseOptions($campo['options_text'] ?? '') : null,
                'is_required' => (bool) ($campo['is_required'] ?? false),
                'sort_order' => $indice,
            ];

            $existente = filled($campo['id'] ?? null)
                ? $form->fields()->whereKey($campo['id'])->first()
                : null;

            if ($existente) {
                $existente->update($atributos);
                $mantidos[] = $existente->id;
                continue;
            }

            $mantidos[] = $form->fields()->create($atributos)->id;
        }

        // Exclusao logica: o campo sai do formulario mas as respostas antigas
        // seguem legiveis na tela de respostas e nas exportacoes.
        $form->fields()->whereNotIn('media_form_fields.id', $mantidos)->delete();
    }

    /** Opções chegam do construtor uma por linha. */
    private function parseOptions(?string $texto): array
    {
        $linhas = preg_split('/\r\n|\r|\n/', (string) $texto) ?: [];

        $opcoes = array_map('trim', $linhas);
        $opcoes = array_filter($opcoes, fn ($opcao) => $opcao !== '');

        return array_values(array_unique($opcoes));
    }
}
