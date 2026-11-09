<?php

namespace App\Services;

use App\Models\StudyForm;
use App\Models\StudyFormQuestion;

class IbadegBookExcerpts
{
    private ?array $index = null;

    public static function questionKey(StudyFormQuestion $question): string
    {
        // Uma edição do enunciado/gabarito invalida a associação antiga.
        return hash('sha256', $question->prompt."\0".$question->correct_answer);
    }

    public function forForm(StudyForm $form): array
    {
        if (! preg_match('/^ibadeg-(\d{2})$/', $form->import_key ?? '', $match)) {
            return [];
        }

        if ($this->index === null) {
            $path = database_path('data/ibadeg-book-excerpts.json');
            $this->index = is_file($path) ? json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR) : [];
        }

        $book = $this->index['books'][$match[1]] ?? null;
        if (! $book) {
            return [];
        }

        $excerpts = [];
        foreach ($form->questions as $question) {
            $passageId = $book['questions'][self::questionKey($question)] ?? null;
            $passage = $book['passages'][$passageId] ?? null;
            if ($passage) {
                $excerpts[$question->id] = [
                    'text' => $passage['text'],
                    'page' => $passage['page'],
                    'book' => $book['title'],
                    'ocr' => $book['ocr'] ?? false,
                ];
            }
        }

        return $excerpts;
    }
}
