<?php

namespace App\Services;

use App\Models\StudyForm;

class IbadegExamPresenter
{
    /** Agrupa a apresentação sem alterar IDs, respostas ou gabaritos armazenados. */
    public function groups(StudyForm $form): array
    {
        $groups = [];
        foreach ($form->questions as $question) {
            $last = array_key_last($groups);
            if ($last === null || $groups[$last]['theme'] !== $question->theme) {
                $groups[] = ['theme' => $question->theme, 'questions' => []];
                $last = array_key_last($groups);
            }
            $groups[$last]['questions'][] = $question;
        }

        $presented = [];
        foreach ($groups as $group) {
            $first = $group['questions'][0];
            $group['instruction'] = $first->prompt;
            $group['kind'] = 'standard';
            $group['items'] = [];
            $parts = [];
            foreach ($group['questions'] as $question) {
                if (! preg_match('/^(.*) — ([a-z]\)|Item \d+:) (.*)$/us', $question->prompt, $match)) {
                    $parts = [];
                    break;
                }
                $parts[] = ['instruction' => $match[1], 'label' => $match[2], 'text' => $match[3]];
            }

            if (count($parts) > 1 && count(array_unique(array_column($parts, 'instruction'))) === 1) {
                $group['instruction'] = $parts[0]['instruction'];
                $group['kind'] = $first->type === 'dissertative' ? 'fill_multi'
                    : (array_column($first->options ?? [], 'key') === ['C', 'E'] ? 'ce' : 'match');
            }

            foreach ($group['questions'] as $index => $question) {
                $group['items'][] = [
                    'question' => $question,
                    'label' => $group['kind'] !== 'standard' ? $parts[$index]['label'] : '',
                    'text' => $group['kind'] !== 'standard' ? $parts[$index]['text'] : '',
                ];
            }
            if ($group['kind'] === 'standard') {
                foreach ($group['items'] as $item) {
                    $presented[] = array_replace($group, [
                        'instruction' => $item['question']->prompt, 'items' => [$item],
                    ]);
                }
            } else {
                $presented[] = $group;
            }
        }

        return $presented;
    }
}
