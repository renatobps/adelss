<?php

namespace App\Services;

use App\Models\StudyFormQuestion;

class StudyFormTextParser
{
    /**
     * Interpreta um texto com temas, tipos e perguntas já respondidas.
     *
     * Exemplo de cabeçalhos reconhecidos:
     * 2. Antigo Testamento
     * Questões Dissertativas
     *
     * @return array<int, array{prompt: string, theme: ?string, type: string, options: ?array, correct_answer: ?string, is_required: bool, sort_order: int}>
     */
    public function parse(string $text): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim($text));
        if ($text === '') {
            return [];
        }

        $lines = explode("\n", $text);
        $questions = [];
        $buffer = [];
        $currentTheme = null;
        $forcedType = null;
        $sortOrder = 0;

        $flush = function () use (&$buffer, &$questions, &$currentTheme, &$forcedType, &$sortOrder) {
            if ($buffer === []) {
                return;
            }

            $block = trim(implode("\n", $buffer));
            $buffer = [];
            if ($block === '') {
                return;
            }

            $parsed = $this->parseQuestionBlock($block, $sortOrder, $currentTheme, $forcedType);
            if ($parsed !== null) {
                $questions[] = $parsed;
                $sortOrder++;
            }
        };

        $total = count($lines);
        for ($i = 0; $i < $total; $i++) {
            $trimmed = trim($lines[$i]);

            if ($trimmed === '') {
                continue;
            }

            $nextMeaningful = $this->nextMeaningfulLine($lines, $i + 1);

            if ($this->isTypeHint($trimmed)) {
                $flush();
                $forcedType = $this->typeFromHint($trimmed);
                continue;
            }

            if ($this->isThemeHeader($trimmed, $nextMeaningful)) {
                $flush();
                $currentTheme = $this->extractThemeTitle($trimmed);

                // Se a próxima linha for o tipo (ex.: Questões Dissertativas), consome já
                if ($nextMeaningful !== null && $this->isTypeHint($nextMeaningful)) {
                    // avança até essa linha no próximo ciclo normalmente
                }
                continue;
            }

            $isNewQuestion = $this->isQuestionStart($trimmed);
            if ($isNewQuestion && $buffer !== []) {
                $flush();
            }

            $buffer[] = $trimmed;
        }

        $flush();

        return $questions;
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function nextMeaningfulLine(array $lines, int $from): ?string
    {
        for ($i = $from; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if ($line !== '') {
                return $line;
            }
        }

        return null;
    }

    private function isTypeHint(string $line): bool
    {
        return $this->typeFromHint($line) !== null;
    }

    private function typeFromHint(string $line): ?string
    {
        $normalized = mb_strtolower(trim($line));
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        if (preg_match('/quest[oõ]es?\s+dissertativas?/u', $normalized)
            || preg_match('/^dissertativas?$/u', $normalized)
            || str_contains($normalized, 'questão dissertativa')
            || str_contains($normalized, 'questoes dissertativas')) {
            return StudyFormQuestion::TYPE_DISSERTATIVE;
        }

        if (preg_match('/m[uú]ltipla\s*escolha/u', $normalized)
            || str_contains($normalized, 'multipla escolha')
            || preg_match('/^quest[oõ]es?\s+(de\s+)?m[uú]ltipla/u', $normalized)
            || preg_match('/^alternativas?$/u', $normalized)) {
            return StudyFormQuestion::TYPE_MULTIPLE_CHOICE;
        }

        if (preg_match('/verdadeiro\s*(ou|\/)\s*falso/u', $normalized)
            || preg_match('/^quest[oõ]es?\s+verdadeiro/u', $normalized)
            || str_contains($normalized, 'v/f')
            || str_contains($normalized, 'certo ou errado')) {
            return StudyFormQuestion::TYPE_TRUE_FALSE;
        }

        return null;
    }

    private function isThemeHeader(string $line, ?string $nextMeaningful): bool
    {
        // Não confundir opções V/F ou alternativas com tema
        if ($this->isOptionOrTrueFalseLine($line)) {
            return false;
        }

        if (preg_match('/^(?:tema|se[cç][aã]o|m[oó]dulo|parte|unidade|cap[ií]tulo)\s*[:.\-]?\s+\S+/iu', $line)) {
            return true;
        }

        if (preg_match('/^#{1,3}\s+\S+/', $line)) {
            return true;
        }

        // "1. Sobre a Bíblia" seguido de "Questões Dissertativas"
        $looksNumberedTitle = preg_match('/^\d+[\.\)\-\:]\s+\S+/u', $line) === 1;
        $hasQuestionMark = str_contains($line, '?');
        $nextIsTypeHint = $nextMeaningful !== null && $this->isTypeHint($nextMeaningful);

        if ($looksNumberedTitle && ! $hasQuestionMark && $nextIsTypeHint) {
            return true;
        }

        // Título sem número, curto, seguido do tipo (ex.: "Sobre a Bíblia" + "Questões Dissertativas")
        if (! $hasQuestionMark && $nextIsTypeHint && ! $this->isQuestionStart($line) && ! $this->isTypeHint($line)) {
            $wordCount = count(preg_split('/\s+/u', $line) ?: []);
            if ($wordCount > 0 && $wordCount <= 8) {
                return true;
            }
        }

        return false;
    }

    private function isOptionOrTrueFalseLine(string $line): bool
    {
        // Apenas alternativas a-d (não 1. 2. 3., que são perguntas/temas)
        if (preg_match('/^[A-Da-d][\.\)\-\:]\s+\S+/u', $line)) {
            return true;
        }

        return preg_match('/^(verdadeiro|falso|v|f)(\s*[\.\):\-]?\s*)?(\*|✅|✔|\(correta\))?$/iu', $line) === 1;
    }

    private function extractThemeTitle(string $line): string
    {
        $title = preg_replace('/^(?:tema|se[cç][aã]o|m[oó]dulo|parte|unidade|cap[ií]tulo)\s*[:.\-]?\s*/iu', '', $line) ?? $line;
        $title = preg_replace('/^#{1,3}\s+/', '', $title) ?? $title;
        $title = preg_replace('/^\d+[\.\)\-\:]\s*/', '', $title) ?? $title;

        return trim($title);
    }

    private function isQuestionStart(string $line): bool
    {
        if ($this->isTypeHint($line) || $this->isThemeHeader($line, null)) {
            return false;
        }

        return preg_match('/^\d+[\.\)\-\:]\s+\S/', $line) === 1
            || preg_match('/^(?:pergunta|quest[aã]o)\s*\d*\s*[:.\-]\s*\S/iu', $line) === 1;
    }

    /**
     * @return array{prompt: string, theme: ?string, type: string, options: ?array, correct_answer: ?string, is_required: bool, sort_order: int}|null
     */
    private function parseQuestionBlock(string $block, int $index, ?string $theme, ?string $forcedType): ?array
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $block)), fn ($l) => $l !== ''));
        if ($lines === []) {
            return null;
        }

        // Ignora bloco que seja só cabeçalho/tema
        if (count($lines) === 1 && ($this->isTypeHint($lines[0]) || $this->isThemeHeader($lines[0], null))) {
            return null;
        }

        $first = preg_replace('/^(?:pergunta|quest[aã]o)\s*\d*\s*[:.\-]\s*/iu', '', $lines[0]) ?? $lines[0];
        $first = preg_replace('/^\d+[\.\)\-\:]\s*/', '', $first) ?? $first;
        $lines[0] = trim($first);

        $explicitAnswer = null;
        $bodyLines = [];
        foreach ($lines as $line) {
            if ($this->isTypeHint($line) || $this->isThemeHeader($line, null)) {
                continue;
            }
            if (preg_match('/^(?:resposta|gabarito|correta|alternativa correta)\s*[:\-]\s*(.+)$/iu', $line, $m)) {
                $explicitAnswer = trim($m[1]);
                continue;
            }
            $bodyLines[] = $line;
        }

        if ($bodyLines === []) {
            return null;
        }

        $optionLines = [];
        $promptLines = [];
        $tfMarkers = [];

        foreach ($bodyLines as $i => $line) {
            if (preg_match('/^([A-Da-d]|[1-9])[\.\)\-\:]\s+(.+)$/u', $line, $m)) {
                $key = strtolower($m[1]);
                $text = trim($m[2]);
                $isCorrect = false;

                if (preg_match('/(\*|✅|✔|\(correta\)|\(certo\)|\(resposta\))\s*$/iu', $text)) {
                    $isCorrect = true;
                    $text = trim(preg_replace('/(\*|✅|✔|\(correta\)|\(certo\)|\(resposta\))\s*$/iu', '', $text) ?? $text);
                }

                $optionLines[] = [
                    'key' => ctype_digit($key) ? chr(96 + (int) $key) : $key,
                    'text' => $text,
                    'is_correct' => $isCorrect,
                ];
                continue;
            }

            if (preg_match('/^(verdadeiro|falso|v|f)(\s*[\.\):\-]?\s*)?(\*|✅|✔|\(correta\))?$/iu', $line, $m)) {
                $value = $this->normalizeTrueFalse($m[1]);
                $tfMarkers[] = [
                    'value' => $value,
                    'is_correct' => ! empty($m[3]),
                ];
                continue;
            }

            if ($i > 0 && preg_match('/^(.+?)(\*|✅|✔)\s*$/u', $line, $m) && count($optionLines) > 0) {
                $optionLines[] = [
                    'key' => chr(97 + count($optionLines)),
                    'text' => trim($m[1]),
                    'is_correct' => true,
                ];
                continue;
            }

            $promptLines[] = $line;
        }

        $prompt = trim(implode(' ', $promptLines));
        if ($prompt === '') {
            $prompt = 'Pergunta '.($index + 1);
        }

        $detectedType = null;

        if (count($tfMarkers) >= 2 || $this->looksLikeTrueFalse($explicitAnswer, $prompt, $bodyLines)) {
            $detectedType = StudyFormQuestion::TYPE_TRUE_FALSE;
        } elseif (count($optionLines) >= 2) {
            $detectedType = StudyFormQuestion::TYPE_MULTIPLE_CHOICE;
        } else {
            $detectedType = StudyFormQuestion::TYPE_DISSERTATIVE;
        }

        // Tipo forçado pelo cabeçalho só quando a detecção automática for ambígua
        // (ex.: dissertativa sem opções). Se há opções/V-F claros, prevalece o conteúdo.
        $type = $detectedType;
        if ($forcedType !== null) {
            if ($detectedType === StudyFormQuestion::TYPE_DISSERTATIVE
                || ($forcedType === $detectedType)
                || ($forcedType === StudyFormQuestion::TYPE_DISSERTATIVE && count($optionLines) < 2 && count($tfMarkers) < 2)) {
                $type = $forcedType;
            }
            if ($forcedType === StudyFormQuestion::TYPE_TRUE_FALSE && count($optionLines) < 2) {
                $type = StudyFormQuestion::TYPE_TRUE_FALSE;
            }
            if ($forcedType === StudyFormQuestion::TYPE_MULTIPLE_CHOICE && count($optionLines) >= 2) {
                $type = StudyFormQuestion::TYPE_MULTIPLE_CHOICE;
            }
            if ($forcedType === StudyFormQuestion::TYPE_DISSERTATIVE && count($optionLines) < 2 && count($tfMarkers) < 2) {
                $type = StudyFormQuestion::TYPE_DISSERTATIVE;
            }
        }

        if ($type === StudyFormQuestion::TYPE_TRUE_FALSE) {
            $correct = null;
            foreach ($tfMarkers as $marker) {
                if ($marker['is_correct']) {
                    $correct = $marker['value'];
                    break;
                }
            }
            if ($correct === null && $explicitAnswer !== null) {
                $correct = $this->normalizeTrueFalse($explicitAnswer);
            }

            return [
                'prompt' => $prompt,
                'theme' => $theme,
                'type' => StudyFormQuestion::TYPE_TRUE_FALSE,
                'options' => [
                    ['key' => 'true', 'text' => 'Verdadeiro'],
                    ['key' => 'false', 'text' => 'Falso'],
                ],
                'correct_answer' => $correct,
                'is_required' => true,
                'sort_order' => $index,
            ];
        }

        if ($type === StudyFormQuestion::TYPE_MULTIPLE_CHOICE) {
            $correct = null;
            foreach ($optionLines as $opt) {
                if ($opt['is_correct']) {
                    $correct = $opt['key'];
                    break;
                }
            }

            if ($correct === null && $explicitAnswer !== null) {
                $answer = strtolower(trim($explicitAnswer));
                if (preg_match('/^[a-d]$/', $answer)) {
                    $correct = $answer;
                } elseif (preg_match('/^[1-9]$/', $answer)) {
                    $correct = chr(96 + (int) $answer);
                } else {
                    foreach ($optionLines as $opt) {
                        if (strcasecmp($opt['text'], $explicitAnswer) === 0) {
                            $correct = $opt['key'];
                            break;
                        }
                    }
                }
            }

            $options = array_map(fn ($opt) => [
                'key' => $opt['key'],
                'text' => $opt['text'],
            ], $optionLines);

            return [
                'prompt' => $prompt,
                'theme' => $theme,
                'type' => StudyFormQuestion::TYPE_MULTIPLE_CHOICE,
                'options' => $options,
                'correct_answer' => $correct,
                'is_required' => true,
                'sort_order' => $index,
            ];
        }

        return [
            'prompt' => $prompt,
            'theme' => $theme,
            'type' => StudyFormQuestion::TYPE_DISSERTATIVE,
            'options' => null,
            'correct_answer' => $explicitAnswer,
            'is_required' => true,
            'sort_order' => $index,
        ];
    }

    private function looksLikeTrueFalse(?string $explicitAnswer, string $prompt, array $bodyLines): bool
    {
        if ($explicitAnswer !== null && $this->normalizeTrueFalse($explicitAnswer) !== null) {
            return true;
        }

        $joined = mb_strtolower(implode(' ', $bodyLines).' '.$prompt);

        return str_contains($joined, 'verdadeiro ou falso')
            || str_contains($joined, 'verdadeiro/falso')
            || str_contains($joined, '(v/f)')
            || str_contains($joined, 'v ou f');
    }

    private function normalizeTrueFalse(string $value): ?string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^a-zà-ú]/iu', '', $value) ?? $value;

        if (in_array($value, ['verdadeiro', 'verdade', 'v', 'true', 'certo', 'sim'], true)) {
            return 'true';
        }

        if (in_array($value, ['falso', 'falsa', 'f', 'false', 'errado', 'nao', 'não'], true)) {
            return 'false';
        }

        return null;
    }
}
