@extends('layouts.porto')

@section('title', 'Agendar publicação')
@section('page-title', 'Agendar publicação Instagram')

@section('breadcrumbs')
    <li><a href="{{ route('midia.index') }}">Mídia</a></li>
    <li><a href="{{ route('midia.instagram.posts.index') }}">Instagram</a></li>
    <li><span>Agendar</span></li>
@endsection

@section('content')
@include('midia.partials.nav', ['active' => 'instagram'])

@unless($instagramConnected)
    <div class="alert alert-warning">Conecte o Instagram antes de agendar. <a href="{{ route('midia.settings') }}">Configurações</a></div>
@endunless

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('midia.instagram.posts.store') }}" enctype="multipart/form-data" id="scheduleForm">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Arquivo da Mídia (Google Drive)</label>
                    <select name="media_file_id" id="media_file_id" class="form-select">
                        <option value="" data-kind="">— Selecionar —</option>
                        @foreach($mediaFiles as $file)
                            @php $kind = str_starts_with((string) $file->mime_type, 'video/') ? 'video' : 'foto'; @endphp
                            <option value="{{ $file->id }}"
                                    data-kind="{{ $kind }}"
                                    @selected(old('media_file_id') == $file->id)>
                                {{ $file->name }} ({{ $file->original_filename }}) — {{ $kind }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Ou envie um arquivo abaixo (foto ou vídeo).</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Upload direto</label>
                    <input type="file" name="media" id="media_upload" class="form-control"
                           accept="image/*,video/mp4,video/quicktime">
                </div>

                <div class="col-12">
                    <label class="form-label">Destinos <span class="text-danger">*</span></label>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach(\App\Models\ScheduledPostDestination::DESTINATIONS as $key => $label)
                            <div class="form-check">
                                <input class="form-check-input destination-check" type="checkbox"
                                       name="destinations[]" value="{{ $key }}" id="dest_{{ $key }}"
                                       @checked(collect(old('destinations', ['feed']))->contains($key))>
                                <label class="form-check-label" for="dest_{{ $key }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                    <div class="form-text" id="destHelp">Reels exige vídeo. Feed+Reels juntos usam uma única publicação otimizada.</div>
                    <div class="text-danger small d-none" id="reelsError">Reels exige vídeo — remova essa opção ou envie um vídeo.</div>
                </div>

                <div class="col-12">
                    <label class="form-label">Legenda</label>
                    <textarea name="caption" class="form-control" rows="4" maxlength="2200">{{ old('caption') }}</textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Publicar em <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="scheduled_for" class="form-control" required
                           value="{{ old('scheduled_for') }}" min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}">
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('midia.instagram.posts.index') }}" class="btn btn-link">Cancelar</a>
                <button class="btn btn-primary" type="submit" id="submitBtn" @disabled(!$instagramConnected)>Agendar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('scheduleForm');
    const select = document.getElementById('media_file_id');
    const upload = document.getElementById('media_upload');
    const reels = document.getElementById('dest_reels');
    const reelsError = document.getElementById('reelsError');
    let uploadKind = '';

    function selectedKind() {
        if (upload.files && upload.files[0]) {
            return upload.files[0].type.startsWith('video/') ? 'video' : 'foto';
        }
        const opt = select.options[select.selectedIndex];
        return opt ? (opt.dataset.kind || '') : '';
    }

    function validateDestinations() {
        const checks = [...document.querySelectorAll('.destination-check:checked')];
        if (!checks.length) {
            alert('Selecione pelo menos um destino (Feed, Reels ou Stories).');
            return false;
        }
        const kind = selectedKind();
        if (reels.checked && kind !== 'video') {
            reelsError.classList.remove('d-none');
            return false;
        }
        reelsError.classList.add('d-none');
        return true;
    }

    upload?.addEventListener('change', () => {
        if (upload.files[0]) {
            uploadKind = upload.files[0].type.startsWith('video/') ? 'video' : 'foto';
            select.value = '';
        }
        validateDestinations();
    });
    select?.addEventListener('change', validateDestinations);
    reels?.addEventListener('change', validateDestinations);

    form?.addEventListener('submit', (e) => {
        if (!validateDestinations()) {
            e.preventDefault();
        }
    });
})();
</script>
@endpush
