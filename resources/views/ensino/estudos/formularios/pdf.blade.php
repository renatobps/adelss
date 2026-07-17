<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ $formulario->title }}</title>

    <style>
        @page {
            margin: 0;
            size: A4 portrait;
        }

        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1a2b24;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        /* Todo o espaçamento visual fica aqui (padrão dos PDFs do projeto) */
        .page {
            padding: 50px;
        }

        .header {
            background-color: #163a32;
            color: #ffffff;
            padding: 10px 12px;
            margin-bottom: 10px;
        }

        .header h1 {
            font-size: 14px;
            margin: 0 0 3px 0;
            word-wrap: break-word;
        }

        .header p {
            font-size: 9px;
            margin: 0;
            opacity: 0.95;
        }

        .description {
            margin: 0 0 10px 0;
            color: #5d7269;
            font-size: 10px;
            word-wrap: break-word;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 12px 0;
            border-bottom: 1px solid #d7e3dd;
        }

        .meta td {
            vertical-align: bottom;
            padding: 4px 0 8px 0;
        }

        .meta .label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #5d7269;
            margin-bottom: 2px;
        }

        .line {
            border-bottom: 1px solid #9db3a8;
            min-height: 14px;
        }

        .section {
            margin: 10px 0 6px 0;
        }

        .section-head {
            background-color: #e8f4ee;
            border-left: 3px solid #1a8f57;
            padding: 6px 8px;
            margin: 0 0 8px 0;
        }

        .section-head .theme {
            font-size: 11px;
            font-weight: bold;
            color: #163a32;
            word-wrap: break-word;
        }

        .section-head .type {
            font-size: 8px;
            color: #1a8f57;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-top: 2px;
        }

        /* Mantém a pergunta inteira junta; seções grandes podem quebrar entre páginas */
        .question {
            margin: 0 0 10px 0;
            page-break-inside: avoid;
        }

        .q-prompt {
            font-weight: bold;
            margin: 0 0 5px 0;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .q-num {
            color: #1a8f57;
        }

        .opt {
            margin: 2px 0 2px 2px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .box {
            display: inline-block;
            width: 9px;
            min-height: 9px;
            border: 1px solid #334;
            margin-right: 4px;
            vertical-align: middle;
        }

        .answer-lines .line {
            margin-top: 8px;
        }

        .gabarito {
            margin-top: 4px;
            font-size: 8px;
            color: #146c43;
            background-color: #eefaf3;
            padding: 3px 5px;
            word-wrap: break-word;
        }

        .footer {
            margin-top: 14px;
            padding-top: 6px;
            border-top: 1px solid #d7e3dd;
            text-align: center;
            font-size: 8px;
            color: #7a8f86;
        }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <h1>{{ $formulario->title }}</h1>
        <p>
            @if($formulario->study)
                Estudo: {{ $formulario->study->name }}
            @endif
            @if(!empty($withAnswers))
                · Gabarito
            @else
                · Questionário
            @endif
        </p>
    </div>

    @if($formulario->description)
        <p class="description">{{ $formulario->description }}</p>
    @endif

    <table class="meta">
        <tr>
            <td style="width:66%;">
                <div class="label">Nome</div>
                <div class="line"></div>
            </td>
            <td style="width:6%;">&nbsp;</td>
            <td style="width:28%;">
                <div class="label">Data</div>
                <div class="line"></div>
            </td>
        </tr>
    </table>

    @php $n = 0; @endphp
    @foreach($sections as $section)
        <div class="section">
            <div class="section-head">
                <div class="theme">{{ $section['theme'] ?: $section['type_label'] }}</div>
                @if($section['theme'])
                    <div class="type">{{ $section['type_label'] }}</div>
                @endif
            </div>

            @foreach($section['questions'] as $question)
                @php $n++; @endphp
                <div class="question">
                    <div class="q-prompt">
                        <span class="q-num">{{ $n }}.</span>
                        {{ $question->prompt }}
                    </div>

                    @if($question->type === 'dissertative')
                        <div class="answer-lines">
                            <div class="line"></div>
                            <div class="line"></div>
                            <div class="line"></div>
                        </div>
                        @if(!empty($withAnswers) && $question->correct_answer)
                            <div class="gabarito">Referência: {{ $question->correct_answer }}</div>
                        @endif
                    @elseif($question->type === 'true_false')
                        <div class="opt"><span class="box"></span> Verdadeiro</div>
                        <div class="opt"><span class="box"></span> Falso</div>
                        @if(!empty($withAnswers) && $question->correct_answer)
                            <div class="gabarito">
                                Resposta: {{ $question->correct_answer === 'true' ? 'Verdadeiro' : 'Falso' }}
                            </div>
                        @endif
                    @else
                        @foreach($question->options ?? [] as $opt)
                            <div class="opt">
                                <span class="box"></span>
                                <strong>{{ strtoupper($opt['key']) }})</strong>
                                {{ $opt['text'] }}
                            </div>
                        @endforeach
                        @if(!empty($withAnswers) && $question->correct_answer)
                            <div class="gabarito">Resposta: {{ strtoupper($question->correct_answer) }}</div>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    @endforeach

    <div class="footer">
        Gerado em {{ now()->format('d/m/Y H:i') }} · {{ config('app.name', 'ADELSS') }}
    </div>
</div>
</body>
</html>
