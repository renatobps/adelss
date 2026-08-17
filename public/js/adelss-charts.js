/**
 * Configuração compartilhada dos gráficos do sistema.
 *
 * Cores, formatação de moeda e regras de responsividade ficam aqui para que
 * nenhuma tela precise repetir essas definições. Funciona com ApexCharts
 * (telas migradas) e com Chart.js (telas ainda não migradas).
 */
window.AdelssCharts = (function () {
    'use strict';

    var MOBILE_BREAKPOINT = 768;
    var MAX_MOBILE_POINTS = 7;
    var MIN_HEIGHT = 200;

    var palette = {
        primary: '#0088CC',
        success: '#1FA855',
        danger: '#DC3545',
        warning: '#F5A623',
        text: '#2E353E',
        textSecondary: '#6C757D',
        border: '#EEF0F2'
    };

    // Mesmo significado de cor já usado no box "Previsão": recebido em azul,
    // pago em laranja, previsto em verde/vermelho.
    var seriesColors = {
        receitas: palette.primary,
        despesas: palette.warning,
        aReceber: palette.success,
        aPagar: palette.danger,
        saldo: palette.primary
    };

    var categoryColors = [
        palette.primary,
        palette.success,
        palette.warning,
        palette.danger,
        '#6F42C1',
        palette.textSecondary
    ];

    var brl = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
    var nf = new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 2 });

    /** Valor completo, para tooltips: R$ 3.200,00 */
    function currency(value) {
        return brl.format(Number(value) || 0);
    }

    /** Número sem moeda, para gráficos de contagem: 1.240 */
    function number(value) {
        return nf.format(Number(value) || 0);
    }

    /** Número abreviado, para eixos de contagem: 3,2 mil */
    function abbreviateNumber(value) {
        var n = Number(value) || 0;
        var abs = Math.abs(n);

        if (abs >= 1000000) {
            return decimal(n / 1000000) + ' mi';
        }
        if (abs >= 1000) {
            return decimal(n / 1000) + ' mil';
        }

        return decimal(n);
    }

    /** Valor abreviado, para eixos: R$ 3,2k */
    function abbreviate(value) {
        var n = Number(value) || 0;
        var abs = Math.abs(n);

        if (abs >= 1000000) {
            return 'R$ ' + decimal(n / 1000000) + 'mi';
        }
        if (abs >= 1000) {
            return 'R$ ' + decimal(n / 1000) + 'k';
        }

        return 'R$ ' + decimal(n);
    }

    function decimal(value) {
        return value.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 1 });
    }

    function isMobile() {
        return window.innerWidth < MOBILE_BREAKPOINT;
    }

    /** Verdadeiro quando ao menos uma das séries tem algum valor diferente de zero. */
    function hasData() {
        for (var i = 0; i < arguments.length; i++) {
            var values = arguments[i] || [];
            for (var j = 0; j < values.length; j++) {
                if ((Number(values[j]) || 0) !== 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Agrupa séries diárias em blocos de 7 dias, somando os valores.
     * Um mês de 31 dias vira 5 grupos, dentro do limite de pontos do celular.
     * Em períodos longos o bloco cresce para não passar do teto de pontos.
     */
    function groupIntoWeeks(labels, seriesList, maxBuckets) {
        var size = 7;
        var limit = maxBuckets || MAX_MOBILE_POINTS;

        if (labels.length > size * limit) {
            size = Math.ceil(labels.length / limit);
        }

        var groupedLabels = [];
        var grouped = seriesList.map(function () {
            return [];
        });

        for (var start = 0; start < labels.length; start += size) {
            var end = Math.min(start + size, labels.length);
            groupedLabels.push(dayOf(labels[start]) + '–' + dayOf(labels[end - 1]));

            seriesList.forEach(function (values, index) {
                var total = 0;
                for (var i = start; i < end; i++) {
                    total += Number((values || [])[i]) || 0;
                }
                grouped[index].push(round(total));
            });
        }

        return { labels: groupedLabels, series: grouped };
    }

    /** Aceita rótulos como 17 ou "17/08" e devolve só o dia. */
    function dayOf(label) {
        return String(label === undefined || label === null ? '' : label).split('/')[0];
    }

    function round(value) {
        return Math.round(value * 100) / 100;
    }

    /**
     * Mantém apenas os últimos pontos da série.
     *
     * Serve para eventos discretos, como reuniões: somá-los por semana
     * distorceria o sentido, então em tela pequena mostramos os mais recentes.
     */
    function lastPoints(labels, seriesList, max) {
        var limit = max || MAX_MOBILE_POINTS;

        if (labels.length <= limit) {
            return { labels: labels.slice(), series: seriesList.map(function (s) { return (s || []).slice(); }) };
        }

        var start = labels.length - limit;

        return {
            labels: labels.slice(start),
            series: seriesList.map(function (s) { return (s || []).slice(start); })
        };
    }

    /**
     * Mantém as maiores fatias e agrupa o resto em "Outros".
     * Roscas com muitas fatias ficam ilegíveis em tela pequena.
     */
    function limitSlices(labels, values, max) {
        max = max || 5;

        var items = labels.map(function (label, index) {
            return { label: label, value: Number(values[index]) || 0 };
        }).sort(function (a, b) {
            return b.value - a.value;
        });

        if (items.length <= max + 1) {
            return {
                labels: items.map(function (i) { return i.label; }),
                values: items.map(function (i) { return i.value; })
            };
        }

        var kept = items.slice(0, max);
        var others = items.slice(max).reduce(function (total, item) {
            return total + item.value;
        }, 0);

        return {
            labels: kept.map(function (i) { return i.label; }).concat('Outros'),
            values: kept.map(function (i) { return i.value; }).concat(round(others))
        };
    }

    function deepMerge(target, source) {
        var result = Object.assign({}, target);

        Object.keys(source || {}).forEach(function (key) {
            var value = source[key];
            var isPlainObject = value && typeof value === 'object' && !Array.isArray(value) && typeof value !== 'function';

            result[key] = isPlainObject && result[key] && typeof result[key] === 'object' && !Array.isArray(result[key])
                ? deepMerge(result[key], value)
                : value;
        });

        return result;
    }

    /**
     * Opções base do ApexCharts: legenda abaixo, eixo abreviado e tooltip com
     * o valor completo.
     *
     * Passe `{ currency: false }` em gráficos que contam coisas em vez de
     * dinheiro (presenças, votos, quantidades).
     */
    function baseOptions(mobile, options) {
        options = options || {};

        var emMoeda = options.currency !== false;
        var formatoEixo = emMoeda ? abbreviate : abbreviateNumber;
        var formatoCompleto = emMoeda ? currency : number;
        var axisFont = mobile ? '10px' : '12px';

        return {
            chart: {
                fontFamily: 'inherit',
                height: Math.max(mobile ? 240 : 320, MIN_HEIGHT),
                toolbar: { show: false },
                zoom: { enabled: false },
                animations: { enabled: !mobile },
                parentHeightOffset: 0
            },
            colors: [seriesColors.receitas, seriesColors.despesas, seriesColors.aReceber, seriesColors.aPagar],
            dataLabels: { enabled: false },
            // Em celular a barra precisa de largura suficiente para o toque.
            plotOptions: {
                bar: {
                    columnWidth: mobile ? '88%' : '55%',
                    borderRadius: 3
                }
            },
            grid: {
                borderColor: palette.border,
                strokeDashArray: 4,
                padding: { left: 4, right: 8 }
            },
            legend: {
                position: 'bottom',
                horizontalAlign: 'center',
                fontSize: mobile ? '12px' : '13px',
                labels: { colors: palette.textSecondary },
                markers: { width: 10, height: 10, radius: 3 },
                itemMargin: { horizontal: 8, vertical: 2 }
            },
            tooltip: {
                shared: true,
                intersect: false,
                style: { fontSize: '12px' },
                y: { formatter: formatoCompleto }
            },
            xaxis: {
                axisBorder: { color: palette.border },
                axisTicks: { color: palette.border },
                tooltip: { enabled: false },
                labels: {
                    rotate: 0,
                    hideOverlappingLabels: true,
                    style: { colors: palette.textSecondary, fontSize: axisFont }
                }
            },
            yaxis: {
                labels: {
                    formatter: formatoEixo,
                    style: { colors: palette.textSecondary, fontSize: axisFont }
                }
            },
            noData: {
                text: 'Sem movimentações neste período',
                style: { color: palette.textSecondary, fontSize: '13px' }
            }
        };
    }

    /**
     * Renderiza um gráfico e o reconstrói quando a tela cruza o breakpoint.
     *
     * `factory(mobile)` devolve as opções completas, o que permite trocar tipo,
     * granularidade e séries entre celular e desktop — algo que a propriedade
     * `responsive` do ApexCharts não cobre sozinha.
     */
    function render(target, factory, options) {
        var element = typeof target === 'string' ? document.getElementById(target) : target;

        if (!element || typeof ApexCharts === 'undefined') {
            return null;
        }

        var mobile = isMobile();
        var chart = new ApexCharts(element, deepMerge(baseOptions(mobile, options), factory(mobile) || {}));
        chart.render();

        var timer = null;
        window.addEventListener('resize', function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                if (isMobile() === mobile) {
                    return;
                }

                mobile = isMobile();
                chart.destroy();
                chart = new ApexCharts(element, deepMerge(baseOptions(mobile, options), factory(mobile) || {}));
                chart.render();
            }, 200);
        });

        return function () {
            return chart;
        };
    }

    /** Linha minimalista, sem eixos nem rótulos — comunica apenas tendência. */
    function sparkline(target, values, color) {
        var element = typeof target === 'string' ? document.getElementById(target) : target;

        if (!element || typeof ApexCharts === 'undefined') {
            return null;
        }

        var chart = new ApexCharts(element, {
            chart: {
                type: 'area',
                height: 48,
                sparkline: { enabled: true },
                animations: { enabled: false }
            },
            colors: [color || palette.primary],
            stroke: { width: 2, curve: 'smooth' },
            fill: { opacity: 0.15 },
            series: [{ name: 'Total', data: (values || []).map(Number) }],
            tooltip: { enabled: false }
        });

        chart.render();

        return chart;
    }

    return {
        MOBILE_BREAKPOINT: MOBILE_BREAKPOINT,
        MAX_MOBILE_POINTS: MAX_MOBILE_POINTS,
        palette: palette,
        seriesColors: seriesColors,
        categoryColors: categoryColors,
        currency: currency,
        abbreviate: abbreviate,
        number: number,
        abbreviateNumber: abbreviateNumber,
        isMobile: isMobile,
        hasData: hasData,
        groupIntoWeeks: groupIntoWeeks,
        lastPoints: lastPoints,
        limitSlices: limitSlices,
        baseOptions: baseOptions,
        deepMerge: deepMerge,
        render: render,
        sparkline: sparkline
    };
})();
