<?php

namespace App\Http\Controllers\Midia;

use App\Http\Controllers\Controller;
use App\Models\MediaForm;
use App\Models\MediaFormAnswer;
use App\Models\MediaFormField;
use App\Models\MediaFormSubmission;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaFormResponseController extends Controller
{
    /** O PDF vira ilegivel e pesado demais acima disso; o CSV nao tem limite. */
    private const LIMITE_PDF = 800;

    public function index(Request $request, MediaForm $mediaForm)
    {
        $filtros = $this->filtros($request);
        $campos = $this->camposDeColuna($mediaForm);

        $submissions = $this->query($mediaForm, $filtros)
            ->with('answers')
            ->paginate(25)
            ->withQueryString();

        return view('midia.formularios.responses', [
            'form' => $mediaForm,
            'fields' => $campos,
            'submissions' => $submissions,
            'filters' => $filtros,
            'filterableFields' => $campos->filter->hasOptions()->values(),
            'totalNoPeriodo' => $this->query($mediaForm, $filtros)->count(),
            'totalGeral' => $mediaForm->submissions()->count(),
        ]);
    }

    public function show(MediaForm $mediaForm, MediaFormSubmission $submission)
    {
        abort_unless($submission->media_form_id === $mediaForm->id, 404);

        $submission->load(['answers.field']);

        return view('midia.formularios.response-show', [
            'form' => $mediaForm,
            'fields' => $this->camposDeColuna($mediaForm),
            'submission' => $submission,
        ]);
    }

    public function destroy(MediaForm $mediaForm, MediaFormSubmission $submission)
    {
        abort_unless($submission->media_form_id === $mediaForm->id, 404);

        $submission->delete();

        return redirect()
            ->route('midia.formularios.responses.index', $mediaForm)
            ->with('success', 'Resposta excluída.');
    }

    public function report(Request $request, MediaForm $mediaForm)
    {
        $filtros = $this->filtros($request);
        $campos = $this->camposDeColuna($mediaForm);

        $submissions = $this->query($mediaForm, $filtros)->get(['id', 'submitted_at']);
        $ids = $submissions->pluck('id')->all();

        $respostas = $ids === []
            ? collect()
            : MediaFormAnswer::whereIn('submission_id', $ids)->get()->groupBy('field_id');

        return view('midia.formularios.report', [
            'form' => $mediaForm,
            'filters' => $filtros,
            'total' => $submissions->count(),
            'primeira' => $submissions->min('submitted_at'),
            'ultima' => $submissions->max('submitted_at'),
            'porDia' => $this->porDia($submissions),
            'resumoEscolhas' => $this->resumoEscolhas($campos, $respostas, $submissions->count()),
            'resumoNumeros' => $this->resumoNumeros($campos, $respostas),
            'camposTexto' => $campos->filter(
                fn (MediaFormField $campo) => !$campo->hasOptions() && $campo->type !== MediaFormField::TYPE_NUMBER
            )->values(),
        ]);
    }

    public function exportPdf(Request $request, MediaForm $mediaForm)
    {
        $filtros = $this->filtros($request);
        $campos = $this->camposDeColuna($mediaForm);

        $total = $this->query($mediaForm, $filtros)->count();
        $submissions = $this->query($mediaForm, $filtros)
            ->with('answers')
            ->limit(self::LIMITE_PDF)
            ->get();

        $pdf = Pdf::loadView('midia.formularios.pdf.responses', [
            'form' => $mediaForm,
            'fields' => $campos,
            'submissions' => $submissions,
            'filters' => $filtros,
            'total' => $total,
            'limite' => self::LIMITE_PDF,
            'geradoEm' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('respostas-'.Str::slug($mediaForm->title).'.pdf');
    }

    public function exportCsv(Request $request, MediaForm $mediaForm): StreamedResponse
    {
        $filtros = $this->filtros($request);
        $campos = $this->camposDeColuna($mediaForm);
        $query = $this->query($mediaForm, $filtros)->with('answers');

        $nomeArquivo = 'respostas-'.Str::slug($mediaForm->title).'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$nomeArquivo.'"',
        ];

        return response()->stream(function () use ($query, $campos, $mediaForm) {
            $saida = fopen('php://output', 'w');

            // BOM UTF-8 e separador ponto-e-virgula: e assim que o Excel em
            // portugues abre o arquivo com acentos e colunas corretas.
            fprintf($saida, chr(0xEF).chr(0xBB).chr(0xBF));

            $cabecalho = ['#', 'Data', 'Hora'];
            if ($mediaForm->requires_identification) {
                $cabecalho[] = 'Nome';
                $cabecalho[] = 'Telefone';
            }
            foreach ($campos as $campo) {
                $cabecalho[] = $campo->trashed() ? $campo->label.' (campo removido)' : $campo->label;
            }
            fputcsv($saida, $cabecalho, ';');

            $query->chunk(200, function ($lote) use ($saida, $campos, $mediaForm) {
                foreach ($lote as $submission) {
                    $respostas = $submission->answersByField();

                    $linha = [
                        $submission->id,
                        optional($submission->submitted_at)->format('d/m/Y'),
                        optional($submission->submitted_at)->format('H:i'),
                    ];

                    if ($mediaForm->requires_identification) {
                        $linha[] = $submission->respondent_name;
                        $linha[] = $submission->respondent_phone;
                    }

                    foreach ($campos as $campo) {
                        $linha[] = $respostas[$campo->id]->value ?? '';
                    }

                    fputcsv($saida, $linha, ';');
                }
            });

            fclose($saida);
        }, 200, $headers);
    }

    /**
     * Campos que viram coluna: os atuais do formulário mais os que foram
     * removidos do construtor mas ainda têm resposta guardada.
     */
    private function camposDeColuna(MediaForm $form)
    {
        return MediaFormField::withTrashed()
            ->where('media_form_id', $form->id)
            ->where(function ($query) {
                $query->whereNull('deleted_at')->orWhereHas('answers');
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    private function filtros(Request $request): array
    {
        return [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'q' => trim((string) $request->input('q', '')),
            'field_id' => $request->input('field_id'),
            'field_value' => $request->input('field_value'),
        ];
    }

    private function query(MediaForm $form, array $filtros)
    {
        $query = $form->submissions()->orderByDesc('submitted_at')->orderByDesc('id');

        if (filled($filtros['start_date'])) {
            $query->whereDate('submitted_at', '>=', $filtros['start_date']);
        }

        if (filled($filtros['end_date'])) {
            $query->whereDate('submitted_at', '<=', $filtros['end_date']);
        }

        if ($filtros['q'] !== '') {
            $busca = $filtros['q'];
            $query->where(function ($externo) use ($busca) {
                $externo->where('respondent_name', 'like', "%{$busca}%")
                    ->orWhere('respondent_phone', 'like', "%{$busca}%")
                    ->orWhereHas('answers', function ($interno) use ($busca) {
                        $interno->where('value', 'like', "%{$busca}%");
                    });
            });
        }

        if (filled($filtros['field_id']) && filled($filtros['field_value'])) {
            $campo = (int) $filtros['field_id'];
            $valor = $filtros['field_value'];
            $query->whereHas('answers', function ($interno) use ($campo, $valor) {
                $interno->where('field_id', $campo)->where('value', 'like', "%{$valor}%");
            });
        }

        return $query;
    }

    /** Série de envios por dia, para o gráfico do relatório. */
    private function porDia($submissions): array
    {
        if ($submissions->isEmpty()) {
            return ['labels' => [], 'valores' => []];
        }

        $contagem = $submissions
            ->groupBy(fn ($submission) => optional($submission->submitted_at)->format('Y-m-d'))
            ->map->count();

        $inicio = Carbon::parse($submissions->min('submitted_at'))->startOfDay();
        $fim = Carbon::parse($submissions->max('submitted_at'))->startOfDay();

        $labels = [];
        $valores = [];
        $cursor = $inicio->copy();

        // O eixo precisa dos dias sem resposta para não achatar o intervalo.
        while ($cursor->lte($fim) && count($labels) <= 366) {
            $chave = $cursor->format('Y-m-d');
            $labels[] = $cursor->format('d/m');
            $valores[] = (int) ($contagem[$chave] ?? 0);
            $cursor->addDay();
        }

        return ['labels' => $labels, 'valores' => $valores];
    }

    /** Contagem por opção nos campos de escolha. */
    private function resumoEscolhas($campos, $respostasPorCampo, int $totalEnvios): array
    {
        $resumo = [];

        foreach ($campos->filter->isCountable() as $campo) {
            $contagem = [];
            foreach ($campo->optionList() as $opcao) {
                $contagem[$opcao] = 0;
            }

            $respondidos = 0;
            foreach ($respostasPorCampo[$campo->id] ?? [] as $resposta) {
                $escolhas = $resposta->selectedValues();
                if ($escolhas === []) {
                    continue;
                }

                $respondidos++;
                foreach ($escolhas as $escolha) {
                    // Opção apagada do campo depois de já ter sido escolhida
                    // continua aparecendo, senão o total não fecha.
                    $contagem[$escolha] = ($contagem[$escolha] ?? 0) + 1;
                }
            }

            arsort($contagem);

            $resumo[] = [
                'campo' => $campo,
                'contagem' => $contagem,
                'respondidos' => $respondidos,
                'semResposta' => max(0, $totalEnvios - $respondidos),
            ];
        }

        return $resumo;
    }

    /** Estatísticas dos campos numéricos. */
    private function resumoNumeros($campos, $respostasPorCampo): array
    {
        $resumo = [];

        foreach ($campos->where('type', MediaFormField::TYPE_NUMBER) as $campo) {
            $numeros = collect($respostasPorCampo[$campo->id] ?? [])
                ->map(fn ($resposta) => $resposta->value)
                ->filter(fn ($valor) => is_numeric($valor))
                ->map(fn ($valor) => (float) $valor)
                ->values();

            if ($numeros->isEmpty()) {
                continue;
            }

            $resumo[] = [
                'campo' => $campo,
                'quantidade' => $numeros->count(),
                'soma' => $numeros->sum(),
                'media' => $numeros->avg(),
                'minimo' => $numeros->min(),
                'maximo' => $numeros->max(),
            ];
        }

        return $resumo;
    }
}
