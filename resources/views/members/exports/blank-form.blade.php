<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Ficha de Cadastro de Membro</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #2E353E; }
        h1 { font-size: 18px; color: #0088CC; margin: 0 0 4px; }
        .sub { color: #6C757D; margin-bottom: 18px; }
        .field { margin-bottom: 14px; }
        .label { font-size: 10px; text-transform: uppercase; letter-spacing: .04em; color: #6C757D; margin-bottom: 4px; }
        .line { border-bottom: 1px solid #2E353E; height: 22px; }
        .row { width: 100%; }
        .col { display: inline-block; vertical-align: top; }
        .col-6 { width: 48%; margin-right: 2%; }
        .col-4 { width: 31%; margin-right: 2%; }
        .box { border: 1px solid #EEF0F2; border-radius: 4px; padding: 10px; margin-top: 10px; min-height: 70px; }
        .section { font-weight: bold; margin: 18px 0 8px; border-bottom: 1px solid #EEF0F2; padding-bottom: 4px; }
    </style>
</head>
<body>
    <h1>Ficha de Cadastro de Membro</h1>
    <div class="sub">Preencha e devolva à secretaria · {{ now()->format('d/m/Y') }}</div>

    <div class="section">Dados pessoais</div>
    <div class="field"><div class="label">Nome completo</div><div class="line"></div></div>
    <div class="row">
        <div class="col col-6"><div class="field"><div class="label">Telefone / WhatsApp</div><div class="line"></div></div></div>
        <div class="col col-6"><div class="field"><div class="label">E-mail</div><div class="line"></div></div></div>
    </div>
    <div class="row">
        <div class="col col-4"><div class="field"><div class="label">Data de nascimento</div><div class="line"></div></div></div>
        <div class="col col-4"><div class="field"><div class="label">Gênero (M/F)</div><div class="line"></div></div></div>
        <div class="col col-4"><div class="field"><div class="label">Estado civil</div><div class="line"></div></div></div>
    </div>
    <div class="row">
        <div class="col col-6"><div class="field"><div class="label">Data de casamento (se houver)</div><div class="line"></div></div></div>
        <div class="col col-6"><div class="field"><div class="label">Data de membresia</div><div class="line"></div></div></div>
    </div>

    <div class="section">Endereço</div>
    <div class="field"><div class="label">Endereço</div><div class="line"></div></div>
    <div class="row">
        <div class="col col-4"><div class="field"><div class="label">Cidade</div><div class="line"></div></div></div>
        <div class="col col-4"><div class="field"><div class="label">UF</div><div class="line"></div></div></div>
        <div class="col col-4"><div class="field"><div class="label">CEP</div><div class="line"></div></div></div>
    </div>

    @if(isset($customFields) && $customFields->count())
        <div class="section">Informações adicionais</div>
        @foreach($customFields as $field)
            <div class="field"><div class="label">{{ $field->name }}</div><div class="line"></div></div>
        @endforeach
    @endif

    <div class="section">Observações</div>
    <div class="box"></div>
</body>
</html>
