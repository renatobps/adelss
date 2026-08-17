{{--
    Carrega o ApexCharts uma única vez por página, mesmo que a tela tenha
    vários gráficos. Inclua este partial em qualquer view que renderize
    gráficos com ApexCharts.
--}}
@once
    @push('styles')
    <style>
        /* Texto curto de apoio ao gráfico, ex.: "Valores agrupados por semana". */
        .chart-note {
            margin-top: -0.35rem;
            font-size: 0.78rem;
            color: #6C757D;
            text-align: center;
        }
    </style>
    @endpush

    @push('scripts')
    <script src="{{ asset('vendor/vendor/apexcharts/apexcharts.min.js') }}"></script>
    @endpush
@endonce
