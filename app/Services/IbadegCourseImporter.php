<?php

namespace App\Services;

use App\Models\Study;
use App\Models\StudyForm;
use App\Models\StudyFormQuestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class IbadegCourseImporter
{
    public const COURSE_NAME = 'Curso de Teologia — IBADEG';

    public function import(string $source): Study
    {
        $source = rtrim($source, '/\\');
        $html = $source.'/SITE PROVAS/index.html';
        if (! is_file($html)) {
            throw new RuntimeException('Página de provas não encontrada: '.$html);
        }
        $exams = $this->parseExams(file_get_contents($html));
        $files = [];
        $questions = [];
        foreach ($exams as $exam) {
            $num = $exam['num'];
            $questions[$num] = $this->questions($exam);
            foreach (['LIVROS' => 'books', 'PROVAS' => 'exams'] as $folder => $target) {
                $matches = glob($source.'/'.$folder.'/'.$num.' - *.pdf');
                if (count($matches) !== 1) {
                    throw new RuntimeException("Esperado um PDF da disciplina {$num} em {$folder}.");
                }
                $files['studies/ibadeg/'.$target.'/'.$num.'.pdf'] = $matches[0];
            }
        }
        $files['studies/ibadeg/exams/todas.pdf'] = $source.'/PROVAS/Todas.pdf';
        foreach ($files as $path => $file) {
            if (! is_file($file) || ! is_readable($file) || file_get_contents($file, false, null, 0, 5) !== '%PDF-') {
                throw new RuntimeException('PDF ausente ou inválido: '.$file);
            }
        }

        // Copia por stream, inclusive a apostila de Homilética (cerca de 30 MB).
        foreach ($files as $path => $file) {
            $stream = fopen($file, 'rb');
            try {
                if (! Storage::disk('public')->put($path, $stream)) {
                    throw new RuntimeException('Não foi possível copiar '.$file);
                }
            } finally {
                fclose($stream);
            }
        }

        return DB::transaction(function () use ($exams, $questions) {
            $study = Study::withTrashed()->firstOrCreate(['name' => self::COURSE_NAME], [
                'category' => 'Curso de Teologia',
                'content' => '<p>Curso de Teologia do IBADEG, com 21 disciplinas. Em cada disciplina, leia a apostila e responda à prova.</p>'
                    .'<p>Ao finalizar, consulte a nota de 0 a 10 e a correção das questões objetivas. As questões abertas apresentam respostas de referência e ficam fora da nota automática. Gabaritos assinalados para revisão foram preservados do material original.</p>',
                'attachment' => 'studies/ibadeg/exams/todas.pdf',
                'attachment_name' => 'IBADEG — Todas as provas.pdf',
                'send_notification' => false,
            ]);
            if ($study->trashed()) {
                throw new RuntimeException('O curso foi removido. Restaure-o antes de importar novamente.');
            }
            foreach ($exams as $exam) {
                $num = $exam['num'];
                // Não substitui perguntas nem apaga tentativas ao executar novamente.
                $form = StudyForm::withTrashed()->firstOrCreate(['import_key' => 'ibadeg-'.$num], [
                    'study_id' => $study->id,
                    'title' => $num.' — '.$exam['title'],
                    'description' => 'Leia a apostila antes de responder. Cada item objetivo vale um ponto; a nota final é proporcional aos acertos, de 0 a 10. Questões abertas ficam fora da nota automática e mostram uma resposta de referência ao final.',
                    'public_slug' => 'ibadeg-'.$num,
                    'is_active' => true,
                    'material_path' => 'studies/ibadeg/books/'.$num.'.pdf',
                    'exam_pdf_path' => 'studies/ibadeg/exams/'.$num.'.pdf',
                ]);
                if ($form->wasRecentlyCreated) {
                    $form->questions()->createMany($questions[$num]);
                }
            }

            return $study;
        });
    }

    public function parseExams(string $html): array
    {
        if (! preg_match('/const\s+EXAMS\s*=\s*/', $html, $match, PREG_OFFSET_CAPTURE)) {
            throw new RuntimeException('Banco de questões EXAMS não encontrado no HTML.');
        }
        // O HTML é uma fonte de dados; nenhum script da página é executado.
        $start = $match[0][1] + strlen($match[0][0]);
        $depth = 0;
        $quoted = false;
        $escaped = false;
        $end = null;
        for ($i = $start, $length = strlen($html); $i < $length; $i++) {
            $char = $html[$i];
            if ($quoted) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === '"') {
                    $quoted = false;
                }
            } elseif ($char === '"') {
                $quoted = true;
            } elseif ($char === '[') {
                $depth++;
            } elseif ($char === ']' && --$depth === 0) {
                $end = $i + 1;
                break;
            }
        }
        if ($end === null) {
            throw new RuntimeException('Banco de questões EXAMS incompleto.');
        }
        $exams = json_decode(substr($html, $start, $end - $start), true, 512, JSON_THROW_ON_ERROR);
        if (array_column($exams, 'num') !== array_map(fn ($n) => sprintf('%02d', $n), range(1, 21))) {
            throw new RuntimeException('O curso deve conter as 21 disciplinas em ordem.');
        }

        return $exams;
    }

    public function questions(array $exam): array
    {
        $rows = [];
        foreach ($exam['questions'] as $question) {
            $base = [
                'theme' => 'Questão '.$question['n'],
                'prompt' => $question['n'].') '.$question['instruction'],
                'type' => StudyFormQuestion::TYPE_MULTIPLE_CHOICE,
                'is_required' => true,
                'review_required' => (bool) ($question['uncertain'] ?? false),
            ];
            switch ($question['type']) {
                case 'ce':
                    foreach ($question['parts'] as $part) {
                        $rows[] = array_replace($base, [
                            'prompt' => $base['prompt'].' — '.$part['letter'].') '.$part['text'],
                            'options' => [['key' => 'C', 'text' => 'C — Certo'], ['key' => 'E', 'text' => 'E — Errado']],
                            'correct_answer' => $part['correct'],
                            'review_required' => $base['review_required'] || ($part['uncertain'] ?? false),
                        ]);
                    }
                    break;
                case 'single':
                    $rows[] = array_replace($base, [
                        'options' => array_map(fn ($p) => ['key' => $p['letter'], 'text' => $p['letter'].') '.$p['text']], $question['parts']),
                        'correct_answer' => $question['correctLetter'],
                    ]);
                    break;
                case 'match':
                    foreach ($question['right'] as $index => $part) {
                        $rows[] = array_replace($base, [
                            'prompt' => $base['prompt'].' — Item '.($index + 1).': '.$part['text'],
                            'options' => array_map(fn ($p) => ['key' => (string) $p['key'], 'text' => $p['key'].') '.$p['text']], $question['left']),
                            'correct_answer' => (string) $part['answerKey'],
                        ]);
                    }
                    break;
                case 'fill':
                    $rows[] = array_replace($base, [
                        'type' => StudyFormQuestion::TYPE_DISSERTATIVE,
                        'correct_answer' => $question['answer'],
                    ]);
                    break;
                case 'fill_multi':
                    foreach ($question['parts'] as $part) {
                        $rows[] = array_replace($base, [
                            'prompt' => $base['prompt'].' — '.$part['letter'].') '.$part['prompt'],
                            'type' => StudyFormQuestion::TYPE_DISSERTATIVE,
                            'correct_answer' => $part['answer'],
                            'review_required' => $base['review_required'] || ($part['uncertain'] ?? false),
                        ]);
                    }
                    break;
                default:
                    throw new RuntimeException('Tipo de questão não reconhecido: '.$question['type']);
            }
        }

        foreach ($rows as $index => &$row) {
            $row['sort_order'] = $index;
            if (mb_strlen($row['correct_answer']) > 255
                || ($row['type'] !== StudyFormQuestion::TYPE_DISSERTATIVE && $row['correct_answer'] === '')) {
                throw new RuntimeException('Gabarito ausente ou muito longo em '.$row['theme']);
            }
            if ($row['type'] === StudyFormQuestion::TYPE_MULTIPLE_CHOICE
                && ! in_array($row['correct_answer'], array_column($row['options'], 'key'), true)) {
                throw new RuntimeException('Gabarito não corresponde às alternativas em '.$row['theme']);
            }
        }

        return $rows;
    }
}
