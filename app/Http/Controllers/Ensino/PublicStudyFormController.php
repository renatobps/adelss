<?php

namespace App\Http\Controllers\Ensino;

use App\Http\Controllers\Controller;
use App\Models\StudyForm;
use App\Models\StudyFormAnswer;
use App\Models\StudyFormQuestion;
use App\Models\StudyFormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicStudyFormController extends Controller
{
    public function show(string $slug)
    {
        $formulario = StudyForm::query()
            ->where('public_slug', $slug)
            ->where('is_active', true)
            ->with(['study', 'questions'])
            ->firstOrFail();

        $sections = $this->buildSections($formulario);

        return view('ensino.estudos.formularios.public', compact('formulario', 'sections'));
    }

    /**
     * Agrupa perguntas consecutivas por tema + tipo (cada grupo = uma guia).
     *
     * @return array<int, array{theme: ?string, type: string, type_label: string, questions: \Illuminate\Support\Collection}>
     */
    private function buildSections(StudyForm $formulario): array
    {
        $sections = [];
        $currentKey = null;

        foreach ($formulario->questions as $question) {
            $key = ($question->theme ?: '').'|'.$question->type;

            if ($key !== $currentKey) {
                $sections[] = [
                    'theme' => $question->theme,
                    'type' => $question->type,
                    'type_label' => $question->typeLabel(),
                    'questions' => collect(),
                ];
                $currentKey = $key;
            }

            $sections[array_key_last($sections)]['questions']->push($question);
        }

        return $sections;
    }

    public function submit(Request $request, string $slug)
    {
        $formulario = StudyForm::query()
            ->where('public_slug', $slug)
            ->where('is_active', true)
            ->with('questions')
            ->firstOrFail();

        $rules = [
            'respondent_name' => 'required|string|max:255',
            'answers' => 'required|array',
        ];

        foreach ($formulario->questions as $question) {
            $key = 'answers.'.$question->id;
            if ($question->is_required) {
                $rules[$key] = 'required';
            } else {
                $rules[$key] = 'nullable';
            }

            if ($question->type === StudyFormQuestion::TYPE_DISSERTATIVE) {
                $rules[$key] = ($question->is_required ? 'required' : 'nullable').'|string|max:10000';
            } elseif ($question->type === StudyFormQuestion::TYPE_TRUE_FALSE) {
                $rules[$key] = ($question->is_required ? 'required' : 'nullable').'|in:true,false';
            } else {
                $allowed = collect($question->options ?? [])->pluck('key')->implode(',');
                $rules[$key] = ($question->is_required ? 'required' : 'nullable').'|in:'.$allowed;
            }
        }

        $validated = $request->validate($rules, [
            'respondent_name.required' => 'Informe o seu nome.',
            'answers.*.required' => 'Esta pergunta é obrigatória.',
        ]);

        DB::transaction(function () use ($formulario, $validated, $request) {
            $score = 0;
            $maxScore = 0;

            $submission = StudyFormSubmission::create([
                'study_form_id' => $formulario->id,
                'respondent_name' => $validated['respondent_name'],
                'ip_address' => $request->ip(),
                'submitted_at' => now(),
            ]);

            foreach ($formulario->questions as $question) {
                $raw = $validated['answers'][$question->id] ?? null;
                $isCorrect = null;
                $answerText = null;
                $selectedOption = null;

                if ($question->type === StudyFormQuestion::TYPE_DISSERTATIVE) {
                    $answerText = is_string($raw) ? trim($raw) : null;
                } else {
                    $selectedOption = is_string($raw) ? $raw : null;
                    if ($question->isAutoGradable()) {
                        $maxScore++;
                        $isCorrect = $selectedOption !== null
                            && (string) $selectedOption === (string) $question->correct_answer;
                        if ($isCorrect) {
                            $score++;
                        }
                    }
                }

                StudyFormAnswer::create([
                    'submission_id' => $submission->id,
                    'question_id' => $question->id,
                    'answer_text' => $answerText,
                    'selected_option' => $selectedOption,
                    'is_correct' => $isCorrect,
                ]);
            }

            $submission->update([
                'score' => $maxScore > 0 ? $score : null,
                'max_score' => $maxScore > 0 ? $maxScore : null,
            ]);
        });

        return redirect()
            ->route('study.forms.public.show', $slug)
            ->with('success', 'Respostas enviadas com sucesso! Obrigado, '.$validated['respondent_name'].'.');
    }
}
