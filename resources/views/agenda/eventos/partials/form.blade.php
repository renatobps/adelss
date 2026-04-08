@php
    $e = $event ?? null;
    $schedules = old('schedules');
    if ($schedules === null && $e) {
        $schedules = $e->scheduleItems->map(fn ($s) => [
            'title' => $s->title,
            'detail' => $s->detail,
            'responsible_name' => $s->responsible_name,
            'existing_responsible_photo' => $s->responsible_photo_path,
            'hh' => $s->time_hh,
            'mm' => $s->time_mm,
        ])->values()->all();
    }
    if (empty($schedules)) {
        $schedules = [[
            'title' => '',
            'detail' => '',
            'responsible_name' => '',
            'existing_responsible_photo' => '',
            'hh' => '',
            'mm' => '',
        ]];
    }

    $regFields = old('registration_fields');
    if ($regFields === null && $e) {
        $regFields = $e->registrationFields->map(fn ($f) => [
            'name' => $f->name,
            'field_type' => $f->field_type,
            'required' => $f->required,
            'options' => $f->options ? implode(', ', $f->options) : '',
        ])->values()->all();
    }
    if (empty($regFields)) {
        $regFields = [['name' => '', 'field_type' => 'text', 'required' => false, 'options' => '']];
    }

    $speakers = old('speakers');
    if ($speakers === null && $e) {
        $speakers = $e->speakers->map(fn ($s) => [
            'name' => $s->name,
            'description' => $s->description,
            'existing_photo' => $s->photo_path,
        ])->values()->all();
    }
    if (empty($speakers)) {
        $speakers = [['name' => '', 'description' => '', 'existing_photo' => '']];
    }
@endphp

<div class="row">
    <div class="col-lg-6">
        <section class="card mb-3">
            <header class="card-header"><h2 class="card-title mb-0">Informações do evento</h2></header>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Banner / foto</label>
                    <input type="file" name="banner_image" class="form-control" accept="image/*">
                    <small class="text-muted d-block mt-1">Tamanho sugerido: 1920x800 px (proporção 12:5).</small>
                    @if($e && $e->banner_image)
                        <small class="text-muted d-block mt-1">Atual: <a href="{{ \App\Models\Event::publicStorageUrl($e->banner_image) }}" target="_blank">ver</a></small>
                    @endif
                </div>
                <div class="mb-3">
                    <label class="form-label">Título *</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $e->title ?? '') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tema do evento</label>
                    <input type="text" name="subtitle" class="form-control" value="{{ old('subtitle', $e->subtitle ?? '') }}" placeholder="Ex.: O fim está chegando">
                    <small class="text-muted d-block mt-1">Aparece abaixo do título na página pública do evento.</small>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Cor do tema</label>
                        <input id="theme-color-input" type="color" name="subtitle_color" class="form-control form-control-color w-100" value="{{ old('subtitle_color', $e->subtitle_color ?? '#f3fbff') }}" title="Cor do texto do tema">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fonte do tema</label>
                        @php
                            $themeFonts = \App\Models\Event::allowedSubtitleFontFamilies();
                            $themeFont = old('subtitle_font_family', $e->subtitle_font_family ?? '');
                            $themeFontLabel = $themeFont !== '' ? $themeFont : 'Padrão (Poppins)';
                        @endphp
                        <select id="theme-font-select" name="subtitle_font_family" class="form-select d-none">
                            <option value="" style="font-family: 'Poppins', sans-serif;">Padrão (Poppins)</option>
                            @foreach($themeFonts as $fontOption)
                                <option value="{{ $fontOption }}" style="font-family: '{{ $fontOption }}', sans-serif;" @selected($themeFont === $fontOption)>{{ $fontOption }} — Exemplo de tema</option>
                            @endforeach
                        </select>
                        <div id="theme-font-picker" class="position-relative">
                            <button type="button" id="theme-font-trigger" class="form-select text-start mt-1" style="font-family:'{{ $themeFont !== '' ? $themeFont : 'Poppins' }}',sans-serif;">
                                {{ $themeFontLabel }}
                            </button>
                            <div id="theme-font-dropdown" class="border rounded bg-white shadow-sm mt-1 d-none" style="position:absolute; z-index:1000; width:100%; max-height:220px; overflow:auto;">
                                <button type="button" class="theme-font-item btn btn-link text-start w-100 px-2 py-1 text-decoration-none" data-font="" style="font-family:'Poppins',sans-serif; color:#0f172a; font-size:1.05rem;">Padrão (Poppins)</button>
                                @foreach($themeFonts as $fontOption)
                                    <button
                                        type="button"
                                        class="theme-font-item btn btn-link text-start w-100 px-2 py-1 text-decoration-none"
                                        data-font="{{ $fontOption }}"
                                        style="font-family:'{{ $fontOption }}',sans-serif; color:#0f172a; font-size:1.08rem;"
                                    >
                                        {{ $fontOption }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <div id="theme-preview-box" class="border rounded px-3 py-2" style="background:#f8fafc;">
                        <small class="text-muted d-block">Prévia do tema</small>
                        <strong id="theme-preview-text">Exemplo: O fim está chegando</strong>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Paleta de cores da página</label>
                    @php
                        $palette = old('page_palette', $e->page_palette ?? 'oceano');
                    @endphp
                    <select name="page_palette" class="form-select">
                        <option value="oceano" @selected($palette === 'oceano')>Oceano (azul)</option>
                        <option value="royal" @selected($palette === 'royal')>Royal (azul escuro)</option>
                        <option value="sunset" @selected($palette === 'sunset')>Sunset (laranja)</option>
                        <option value="forest" @selected($palette === 'forest')>Forest (verde)</option>
                        <option value="grape" @selected($palette === 'grape')>Grape (roxo)</option>
                        <option value="rose" @selected($palette === 'rose')>Rose (rosa)</option>
                    </select>
                    <small class="text-muted d-block mt-1">Altera cores de botões, destaques e blocos da landing pública.</small>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Data inicial *</label>
                        <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $e ? $e->start_date->format('Y-m-d') : '') }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Hora inicial</label>
                        <input type="time" name="start_time" class="form-control" value="{{ old('start_time', $e && !$e->all_day ? $e->start_date->format('H:i') : '') }}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Data final</label>
                        <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $e && $e->end_date ? $e->end_date->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Hora final</label>
                        <input type="time" name="end_time" class="form-control" value="{{ old('end_time', $e && $e->end_date && !$e->all_day ? $e->end_date->format('H:i') : '') }}">
                    </div>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" name="all_day" value="1" class="form-check-input" id="all_day" {{ old('all_day', $e?->all_day ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="all_day">Dia inteiro</label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Categoria</label>
                    <select name="category_id" class="form-select">
                        <option value="">—</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('category_id', $e->category_id ?? null) == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <hr>
                <p class="text-muted small mb-2">Campos da inscrição (padrão)</p>
                <div class="form-check"><input type="checkbox" name="phone_required" value="1" class="form-check-input" id="pr" {{ old('phone_required', $e?->phone_required ?? false) ? 'checked' : '' }}><label class="form-check-label" for="pr">Telefone obrigatório</label></div>
                <div class="form-check"><input type="checkbox" name="address_required" value="1" class="form-check-input" id="ar" {{ old('address_required', $e?->address_required ?? false) ? 'checked' : '' }}><label class="form-check-label" for="ar">Endereço obrigatório</label></div>
                <div class="form-check"><input type="checkbox" name="email_required" value="1" class="form-check-input" id="er" {{ old('email_required', $e?->email_required ?? true) ? 'checked' : '' }}><label class="form-check-label" for="er">E-mail obrigatório</label></div>
                <div class="form-check"><input type="checkbox" name="hide_phone" value="1" class="form-check-input" id="hp" {{ old('hide_phone', $e?->hide_phone ?? false) ? 'checked' : '' }}><label class="form-check-label" for="hp">Ocultar telefone</label></div>
                <div class="form-check mb-3"><input type="checkbox" name="hide_address" value="1" class="form-check-input" id="ha" {{ old('hide_address', $e?->hide_address ?? false) ? 'checked' : '' }}><label class="form-check-label" for="ha">Ocultar endereço</label></div>
                <div class="mb-3">
                    <label class="form-label">Notificar e-mails (separados por vírgula)</label>
                    <textarea name="notify_emails" class="form-control" rows="2" placeholder="a@email.com, b@email.com">{{ old('notify_emails', $e->notify_emails ?? '') }}</textarea>
                </div>
                <div class="form-check mb-0">
                    <input type="checkbox" name="registration_enabled" value="1" class="form-check-input" id="reg_en" {{ old('registration_enabled', $e?->registration_enabled ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="reg_en">Inscrições abertas</label>
                </div>
            </div>
        </section>

        <section class="card mb-3">
            <header class="card-header"><h2 class="card-title mb-0">Pagamento</h2></header>
            <div class="card-body">
                <div class="mb-2">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="is_paid" id="free" value="0" @checked(!old('is_paid', $e?->is_paid ?? false))>
                        <label class="form-check-label" for="free">Gratuito</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="is_paid" id="paid" value="1" @checked(old('is_paid', $e?->is_paid ?? false))>
                        <label class="form-check-label" for="paid">Pago</label>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Valor (R$)</label>
                    <input type="number" step="0.01" min="0" name="price" class="form-control" value="{{ old('price', $e->price ?? '') }}">
                </div>
                <div class="mb-0">
                    <label class="form-label">Quantidade de vagas (vazio = ilimitado)</label>
                    <input type="number" min="0" name="max_spots" class="form-control" value="{{ old('max_spots', $e->max_spots ?? '') }}">
                </div>
            </div>
        </section>

        <section class="card mb-3">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title mb-0">Programação</h2>
                <button type="button" class="btn btn-primary btn-sm rounded-circle" id="add-schedule" title="Adicionar">+</button>
            </header>
            <div class="card-body" id="schedule-rows">
                @foreach($schedules as $i => $row)
                    <div class="border rounded p-2 mb-2 schedule-row">
                        <input type="text" name="schedules[{{ $i }}][title]" class="form-control mb-2" placeholder="Título" value="{{ $row['title'] ?? '' }}">
                        <input type="text" name="schedules[{{ $i }}][detail]" class="form-control mb-2" placeholder="Detalhe" value="{{ $row['detail'] ?? '' }}">
                        <input type="text" name="schedules[{{ $i }}][responsible_name]" class="form-control mb-2" placeholder="Nome do responsável" value="{{ $row['responsible_name'] ?? '' }}">
                        @if(!empty($row['existing_responsible_photo']))
                            <input type="hidden" name="schedules[{{ $i }}][existing_responsible_photo]" value="{{ $row['existing_responsible_photo'] }}">
                            <div class="mb-2">
                                <img src="{{ \App\Models\Event::publicStorageUrl($row['existing_responsible_photo']) }}" alt="" style="max-height:72px;border-radius:8px;">
                            </div>
                        @endif
                        <input type="file" name="schedules[{{ $i }}][responsible_photo]" class="form-control form-control-sm mb-2" accept="image/*">
                        <small class="text-muted d-block mb-2">Foto do responsável: sugerido 400x400 px.</small>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="number" name="schedules[{{ $i }}][hh]" class="form-control" placeholder="HH" min="0" max="23" style="max-width:80px" value="{{ $row['hh'] ?? '' }}">
                            <span>:</span>
                            <input type="number" name="schedules[{{ $i }}][mm]" class="form-control" placeholder="MM" min="0" max="59" style="max-width:80px" value="{{ $row['mm'] ?? '' }}">
                            <button type="button" class="btn btn-outline-danger btn-sm remove-row ms-auto">×</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="card mb-3">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title mb-0">Campos adicionais (inscrição)</h2>
                <button type="button" class="btn btn-primary btn-sm rounded-circle" id="add-field" title="Adicionar">+</button>
            </header>
            <div class="card-body" id="field-rows">
                @foreach($regFields as $i => $row)
                    <div class="border rounded p-2 mb-2 field-row">
                        <input type="text" name="registration_fields[{{ $i }}][name]" class="form-control mb-2" placeholder="Nome do campo" value="{{ $row['name'] ?? '' }}">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <select name="registration_fields[{{ $i }}][field_type]" class="form-select">
                                    @foreach(['text' => 'Texto', 'textarea' => 'Área de texto', 'checkbox' => 'Checkbox', 'radio' => 'Radio', 'select' => 'Seleção'] as $val => $label)
                                        <option value="{{ $val }}" @selected(($row['field_type'] ?? 'text') === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-2">
                                    <input type="checkbox" name="registration_fields[{{ $i }}][required]" value="1" class="form-check-input" @checked(!empty($row['required']))>
                                    <label class="form-check-label">Obrigatório</label>
                                </div>
                            </div>
                        </div>
                        <input type="text" name="registration_fields[{{ $i }}][options]" class="form-control mt-2" placeholder="Opções (radio/seleção), separadas por vírgula" value="{{ $row['options'] ?? '' }}">
                        <button type="button" class="btn btn-outline-danger btn-sm mt-2 remove-row">Remover</button>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="col-lg-6">
        <section class="card mb-3">
            <header class="card-header"><h2 class="card-title mb-0">Detalhes</h2></header>
            <div class="card-body">
                <label class="form-label">Sobre o evento</label>
                <x-rich-text-editor name="about_html" id="about_html" :value="old('about_html', $e->about_html ?? '')" :min-height="240" :upload-url="route('agenda.eventos.editor-upload')" />
                <label class="form-label mt-3">Descrição curta (calendário)</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $e->description ?? '') }}</textarea>
            </div>
        </section>

        <section class="card mb-3">
            <header class="card-header"><h2 class="card-title mb-0">Local</h2></header>
            <div class="card-body">
                <textarea name="location" class="form-control" rows="3" placeholder="Endereço ou link">{{ old('location', $e->location ?? '') }}</textarea>
                <label class="form-label mt-3">Fotos do local</label>
                <input type="file" name="location_photos[]" class="form-control" accept="image/*" multiple>
                <small class="text-muted d-block mt-1">Tamanho sugerido: 1200x800 px (proporção 3:2).</small>
                @if($e && count($e->locationPhotoPublicUrls()))
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        @foreach($e->locationPhotoPublicUrls() as $photoUrl)
                            <img src="{{ $photoUrl }}" alt="" style="height:64px;border-radius:8px;">
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section class="card mb-3">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title mb-0">Palestrantes</h2>
                <button type="button" class="btn btn-primary btn-sm rounded-circle" id="add-speaker" title="Adicionar">+</button>
            </header>
            <div class="card-body" id="speaker-rows">
                @foreach($speakers as $i => $row)
                    <div class="border rounded p-2 mb-2 speaker-row">
                        @if(!empty($row['existing_photo']))
                            <input type="hidden" name="speakers[{{ $i }}][existing_photo]" value="{{ $row['existing_photo'] }}">
                        @endif
                        <div class="row g-2">
                            <div class="col-md-3 text-center">
                                <label class="form-label small">Foto</label>
                                @if(!empty($row['existing_photo']))
                                    <div><img src="{{ \App\Models\Event::publicStorageUrl($row['existing_photo']) }}" alt="" style="max-height:80px;border-radius:8px;"></div>
                                @endif
                                <input type="file" name="speakers[{{ $i }}][photo]" class="form-control form-control-sm mt-1" accept="image/*">
                                <small class="text-muted d-block mt-1">Sugerido: 400x400 px.</small>
                            </div>
                            <div class="col-md-9">
                                <input type="text" name="speakers[{{ $i }}][name]" class="form-control mb-2" placeholder="Nome" value="{{ $row['name'] ?? '' }}">
                                <textarea name="speakers[{{ $i }}][description]" class="form-control" rows="2" placeholder="Descrição">{{ $row['description'] ?? '' }}</textarea>
                                <button type="button" class="btn btn-outline-danger btn-sm mt-2 remove-row">Remover</button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</div>

@push('scripts')
<script>
(function() {
    let si = {{ count($schedules) }};
    let fi = {{ count($regFields) }};
    let pi = {{ count($speakers) }};

    document.getElementById('add-schedule')?.addEventListener('click', function() {
        var wrap = document.getElementById('schedule-rows');
        var div = document.createElement('div');
        div.className = 'border rounded p-2 mb-2 schedule-row';
        div.innerHTML = '<input type="text" name="schedules['+si+'][title]" class="form-control mb-2" placeholder="Título">' +
            '<input type="text" name="schedules['+si+'][detail]" class="form-control mb-2" placeholder="Detalhe">' +
            '<input type="text" name="schedules['+si+'][responsible_name]" class="form-control mb-2" placeholder="Nome do responsável">' +
            '<input type="file" name="schedules['+si+'][responsible_photo]" class="form-control form-control-sm mb-2" accept="image/*">' +
            '<small class="text-muted d-block mb-2">Foto do responsável: sugerido 400x400 px.</small>' +
            '<div class="d-flex gap-2 align-items-center">' +
            '<input type="number" name="schedules['+si+'][hh]" class="form-control" placeholder="HH" min="0" max="23" style="max-width:80px">' +
            '<span>:</span>' +
            '<input type="number" name="schedules['+si+'][mm]" class="form-control" placeholder="MM" min="0" max="59" style="max-width:80px">' +
            '<button type="button" class="btn btn-outline-danger btn-sm remove-row ms-auto">×</button></div>';
        wrap.appendChild(div);
        si++;
    });

    document.getElementById('add-field')?.addEventListener('click', function() {
        var wrap = document.getElementById('field-rows');
        var div = document.createElement('div');
        div.className = 'border rounded p-2 mb-2 field-row';
        div.innerHTML = '<input type="text" name="registration_fields['+fi+'][name]" class="form-control mb-2" placeholder="Nome do campo">' +
            '<div class="row g-2"><div class="col-md-6"><select name="registration_fields['+fi+'][field_type]" class="form-select">' +
            '<option value="text">Texto</option><option value="textarea">Área de texto</option><option value="checkbox">Checkbox</option>' +
            '<option value="radio">Radio</option><option value="select">Seleção</option></select></div>' +
            '<div class="col-md-6"><div class="form-check mt-2"><input type="checkbox" name="registration_fields['+fi+'][required]" value="1" class="form-check-input"><label class="form-check-label">Obrigatório</label></div></div></div>' +
            '<input type="text" name="registration_fields['+fi+'][options]" class="form-control mt-2" placeholder="Opções (radio/seleção), separadas por vírgula">' +
            '<button type="button" class="btn btn-outline-danger btn-sm mt-2 remove-row">Remover</button>';
        wrap.appendChild(div);
        fi++;
    });

    document.getElementById('add-speaker')?.addEventListener('click', function() {
        var wrap = document.getElementById('speaker-rows');
        var div = document.createElement('div');
        div.className = 'border rounded p-2 mb-2 speaker-row';
        div.innerHTML = '<div class="row g-2"><div class="col-md-3 text-center"><label class="form-label small">Foto</label>' +
            '<input type="file" name="speakers['+pi+'][photo]" class="form-control form-control-sm mt-1" accept="image/*">' +
            '<small class="text-muted d-block mt-1">Sugerido: 400x400 px.</small></div>' +
            '<div class="col-md-9"><input type="text" name="speakers['+pi+'][name]" class="form-control mb-2" placeholder="Nome">' +
            '<textarea name="speakers['+pi+'][description]" class="form-control" rows="2" placeholder="Descrição"></textarea>' +
            '<button type="button" class="btn btn-outline-danger btn-sm mt-2 remove-row">Remover</button></div></div>';
        wrap.appendChild(div);
        pi++;
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row') || e.target.closest('.remove-row')) {
            var btn = e.target.classList.contains('remove-row') ? e.target : e.target.closest('.remove-row');
            var row = btn.closest('.schedule-row, .field-row, .speaker-row');
            if (row && row.parentNode.children.length > 1) row.remove();
        }
    });

    var themeInput = document.querySelector('input[name="subtitle"]');
    var themeColorInput = document.getElementById('theme-color-input');
    var themeFontSelect = document.getElementById('theme-font-select');
    var themeFontTrigger = document.getElementById('theme-font-trigger');
    var themeFontDropdown = document.getElementById('theme-font-dropdown');
    var previewText = document.getElementById('theme-preview-text');
    var previewBox = document.getElementById('theme-preview-box');

    var updateThemePreview = function() {
        if (!previewText) return;
        var text = (themeInput && themeInput.value.trim()) ? themeInput.value.trim() : 'Exemplo: O fim está chegando';
        var color = (themeColorInput && themeColorInput.value) ? themeColorInput.value : '#f3fbff';
        var font = (themeFontSelect && themeFontSelect.value) ? themeFontSelect.value : 'Poppins';
        previewText.textContent = text;
        previewText.style.color = color;
        previewText.style.fontFamily = "'" + font + "', sans-serif";
        previewText.style.fontSize = '1.2rem';
        previewText.style.fontWeight = '800';
        if (previewBox) {
            previewBox.style.borderColor = color;
        }
    };

    themeInput && themeInput.addEventListener('input', updateThemePreview);
    themeColorInput && themeColorInput.addEventListener('input', updateThemePreview);
    themeFontSelect && themeFontSelect.addEventListener('change', updateThemePreview);
    themeFontTrigger && themeFontTrigger.addEventListener('click', function() {
        if (!themeFontDropdown) return;
        themeFontDropdown.classList.toggle('d-none');
    });
    document.querySelectorAll('.theme-font-item').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!themeFontSelect) return;
            var selectedFont = btn.getAttribute('data-font') || '';
            themeFontSelect.value = selectedFont;
            if (themeFontTrigger) {
                var label = selectedFont || 'Padrão (Poppins)';
                var cssFont = selectedFont || 'Poppins';
                themeFontTrigger.textContent = label;
                themeFontTrigger.style.fontFamily = "'" + cssFont + "', sans-serif";
            }
            if (themeFontDropdown) {
                themeFontDropdown.classList.add('d-none');
            }
            themeFontSelect.dispatchEvent(new Event('change'));
        });
    });
    document.addEventListener('click', function(e) {
        if (!themeFontDropdown || !themeFontTrigger) return;
        if (!e.target.closest('#theme-font-picker')) {
            themeFontDropdown.classList.add('d-none');
        }
    });
    updateThemePreview();
})();
</script>
@endpush
