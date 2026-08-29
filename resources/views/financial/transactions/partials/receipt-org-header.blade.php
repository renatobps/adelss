@php
    $orgName = 'Assembleia de Deus de Luziânia';
    $orgPresident = 'Pr. Sebastião Tavares da Silva';
    $orgAddress = 'Avenida do Contorno, Quadra 84, Lotes 1/4, Setor Leste, Luziânia-GO';
    $orgCnpj = '02.289.114/0001-29';
    $orgPhone = '(61) 3622.1526';
    if (empty($logoSrc) && ! empty($logoPath) && is_file($logoPath)) {
        $logoSrc = $logoPath;
    }
@endphp
<div class="org-header">
    <table class="org-header-table">
        <tr>
            <td class="org-logo-cell">
                @if(!empty($logoSrc))
                    <img src="{{ $logoSrc }}" class="org-logo" alt="">
                @endif
            </td>
            <td class="org-info-cell">
                <div class="org-name">{{ $orgName }}</div>
                <table class="org-meta">
                    <tr>
                        <td class="org-addr">Sede: {{ $orgAddress }}</td>
                        <td class="org-right">
                            Presidente: {{ $orgPresident }}<br>
                            CNPJ: {{ $orgCnpj }} &nbsp; Tel. {{ $orgPhone }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
