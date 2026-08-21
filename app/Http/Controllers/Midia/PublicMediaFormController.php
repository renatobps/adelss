<?php

namespace App\Http\Controllers\Midia;

use App\Http\Controllers\Controller;
use App\Models\MediaForm;
use App\Models\MediaFormField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicMediaFormController extends Controller
{
    public function show(string $slug)
    {
        $form = MediaForm::query()
            ->where('public_slug', $slug)
            ->where('is_active', true)
            ->with('fields')
            ->firstOrFail();

        return view('midia.formularios.public', ['form' => $form]);
    }

    public function submit(Request $request, string $slug)
    {
        $form = MediaForm::query()
            ->where('public_slug', $slug)
            ->where('is_active', true)
            ->with('fields')
            ->firstOrFail();

        if (!$form->is_accepting_responses) {
            return back()->with('error', 'Este formulário não está mais recebendo respostas.');
        }

        $validado = $request->validate(
            $this->rules($form),
            [],
            $this->attributeNames($form)
        );

        DB::transaction(function () use ($form, $validado, $request) {
            $submission = $form->submissions()->create([
                'respondent_name' => $form->requires_identification ? ($validado['respondent_name'] ?? null) : null,
                'respondent_phone' => $form->requires_identification ? ($validado['respondent_phone'] ?? null) : null,
                'ip_address' => $request->ip(),
                'submitted_at' => now(),
            ]);

            foreach ($form->fields as $campo) {
                $bruto = $validado['answers'][$campo->id] ?? null;

                if ($campo->acceptsMultipleValues()) {
                    $escolhas = array_values(array_filter((array) $bruto, fn ($item) => filled($item)));

                    if ($escolhas === []) {
                        continue;
                    }

                    $submission->answers()->create([
                        'field_id' => $campo->id,
                        // A forma legível fica em `value` para busca e exportação
                        // usarem uma coluna só.
                        'value' => implode(', ', $escolhas),
                        'value_list' => $escolhas,
                    ]);

                    continue;
                }

                if (blank($bruto)) {
                    continue;
                }

                $submission->answers()->create([
                    'field_id' => $campo->id,
                    'value' => is_scalar($bruto) ? (string) $bruto : null,
                ]);
            }
        });

        return redirect()
            ->route('formularios.public.show', $form->public_slug)
            ->with('form_submitted', true);
    }

    /** Regras montadas a partir dos campos cadastrados no construtor. */
    private function rules(MediaForm $form): array
    {
        $rules = [];

        if ($form->requires_identification) {
            $rules['respondent_name'] = 'required|string|max:255';
            $rules['respondent_phone'] = 'nullable|string|max:40';
        }

        foreach ($form->fields as $campo) {
            $chave = 'answers.'.$campo->id;
            $obrigatorio = $campo->is_required;

            if ($campo->acceptsMultipleValues()) {
                $rules[$chave] = ($obrigatorio ? 'required' : 'nullable').'|array';
                $rules[$chave.'.*'] = 'string|in:'.implode(',', $campo->optionList());

                continue;
            }

            $base = $obrigatorio ? 'required' : 'nullable';

            $rules[$chave] = match ($campo->type) {
                MediaFormField::TYPE_TEXTAREA => $base.'|string|max:5000',
                MediaFormField::TYPE_NUMBER => $base.'|numeric',
                MediaFormField::TYPE_DATE => $base.'|date',
                MediaFormField::TYPE_EMAIL => $base.'|email|max:255',
                MediaFormField::TYPE_PHONE => $base.'|string|max:40',
                MediaFormField::TYPE_SELECT,
                MediaFormField::TYPE_RADIO => $base.'|in:'.implode(',', $campo->optionList()),
                default => $base.'|string|max:500',
            };
        }

        return $rules;
    }

    /** Sem isso o erro apareceria como "answers.12" em vez do título do campo. */
    private function attributeNames(MediaForm $form): array
    {
        $nomes = [
            'respondent_name' => 'nome',
            'respondent_phone' => 'telefone',
        ];

        foreach ($form->fields as $campo) {
            $nomes['answers.'.$campo->id] = mb_strtolower($campo->label);
            $nomes['answers.'.$campo->id.'.*'] = mb_strtolower($campo->label);
        }

        return $nomes;
    }
}
