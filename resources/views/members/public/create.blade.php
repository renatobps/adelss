<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Membro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #2E353E;
            --primary: #0088CC;
            --border: #EEF0F2;
            --text: #2E353E;
            --text-secondary: #6C757D;
        }
        body {
            background: linear-gradient(160deg, #e8f4fb 0%, #f7f9fb 45%, #ffffff 100%);
            min-height: 100vh;
            color: var(--text);
        }
        .public-wrap { max-width: 720px; margin: 2rem auto; padding: 0 1rem 3rem; }
        .public-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 1.75rem;
            box-shadow: 0 10px 30px rgba(46,53,62,.06);
        }
        .public-card h1 { font-size: 1.45rem; font-weight: 700; color: var(--primary); }
        .public-card .lead { color: var(--text-secondary); font-size: .95rem; }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: #0094DD; border-color: #0094DD; }
    </style>
</head>
<body>
<div class="public-wrap">
    <div class="public-card">
        <h1>Cadastro de membro</h1>
        <p class="lead mb-4">Preencha seus dados. Um administrador irá revisar antes de ativar seu cadastro.</p>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('members.public.store', $token) }}" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Nome completo *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Telefone / WhatsApp *</label>
                    <input type="tel" name="phone" id="phoneInput" class="form-control" value="{{ old('phone') }}"
                           placeholder="(00) 00000-0000" inputmode="numeric" maxlength="15" autocomplete="tel"
                           pattern="\(\d{2}\) \d{5}-\d{4}" title="Informe no formato (00) 00000-0000" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">E-mail</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Gênero</label>
                    <select name="gender" class="form-select">
                        <option value="">Selecione...</option>
                        <option value="M" @selected(old('gender') === 'M')>Masculino</option>
                        <option value="F" @selected(old('gender') === 'F')>Feminino</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estado civil</label>
                    <select name="marital_status" class="form-select">
                        <option value="">Selecione...</option>
                        <option value="solteiro" @selected(old('marital_status') === 'solteiro')>Solteiro(a)</option>
                        <option value="casado" @selected(old('marital_status') === 'casado')>Casado(a)</option>
                        <option value="divorciado" @selected(old('marital_status') === 'divorciado')>Divorciado(a)</option>
                        <option value="viuvo" @selected(old('marital_status') === 'viuvo')>Viúvo(a)</option>
                        <option value="uniao_estavel" @selected(old('marital_status') === 'uniao_estavel')>União estável</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nascimento</label>
                    <input type="date" name="birth_date" class="form-control" value="{{ old('birth_date') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Data de casamento</label>
                    <input type="date" name="marriage_date" class="form-control" value="{{ old('marriage_date') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Foto</label>
                    <input type="file" name="photo" class="form-control" accept="image/*">
                </div>
                <div class="col-12">
                    <label class="form-label">Endereço</label>
                    <input type="text" name="address" class="form-control" value="{{ old('address') }}">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Cidade</label>
                    <input type="text" name="city" class="form-control" value="{{ old('city') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">UF</label>
                    <input type="text" name="state" class="form-control" maxlength="2" value="{{ old('state') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">CEP</label>
                    <input type="text" name="zip_code" class="form-control" value="{{ old('zip_code') }}">
                </div>

                @include('members.partials.custom-fields', ['customFields' => $customFields])

                <div class="col-12">
                    <label class="form-label">Observações</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Enviar cadastro</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
    (function () {
        const input = document.getElementById('phoneInput');
        function applyMask(value) {
            const digits = value.replace(/\D/g, '').slice(0, 11);
            if (digits.length === 0) return '';
            if (digits.length <= 2) return '(' + digits;
            if (digits.length <= 7) return '(' + digits.slice(0, 2) + ') ' + digits.slice(2);
            return '(' + digits.slice(0, 2) + ') ' + digits.slice(2, 7) + '-' + digits.slice(7);
        }
        input.addEventListener('input', function () {
            this.value = applyMask(this.value);
        });
        input.value = applyMask(input.value);
    })();
</script>
</body>
</html>
