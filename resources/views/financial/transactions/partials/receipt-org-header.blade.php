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
            <td class="org-left">
                @if(!empty($logoSrc))
                    <img src="{{ $logoSrc }}" class="org-logo" alt="CDEL">
                @endif
                <div class="org-name">{{ $orgName }}</div>
                <div class="org-addr">Sede: {{ $orgAddress }}</div>
            </td>
            <td class="org-right">
                Presidente: {{ $orgPresident }}<br>
                CNPJ: {{ $orgCnpj }}<br>
                Tel. {{ $orgPhone }}
            </td>
        </tr>
    </table>
</div>
