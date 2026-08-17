{{--
    Mensagem curta no lugar de um gráfico sem dados.
    Ocupa pouca altura, em vez de deixar eixos vazios ocupando a tela.

    Uso: @include('partials.chart-empty', ['message' => 'Sem movimentações neste período'])
--}}
@once
    @push('styles')
    <style>
        .chart-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            min-height: 120px;
            padding: 1.25rem 1rem;
            text-align: center;
            color: #6C757D;
        }
        .chart-empty__icon {
            font-size: 1.6rem;
            line-height: 1;
            color: #EEF0F2;
        }
        .chart-empty__message {
            margin: 0;
            font-size: 0.9rem;
        }
        .chart-empty__hint {
            margin: 0;
            font-size: 0.8rem;
            opacity: 0.8;
        }
    </style>
    @endpush
@endonce

<div class="chart-empty">
    <i class="bx {{ $icon ?? 'bx-line-chart' }} chart-empty__icon"></i>
    <p class="chart-empty__message">{{ $message ?? 'Sem movimentações neste período' }}</p>
    @isset($hint)
        <p class="chart-empty__hint">{{ $hint }}</p>
    @endisset
</div>
