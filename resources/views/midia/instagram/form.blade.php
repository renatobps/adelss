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

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('midia.instagram.posts.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Foto da Mídia (Google Drive)</label>
                    <select name="media_file_id" class="form-select">
                        <option value="">— Selecionar —</option>
                        @foreach($photos as $photo)
                            <option value="{{ $photo->id }}" @selected(old('media_file_id') == $photo->id)>
                                {{ $photo->name }} ({{ $photo->original_filename }})
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Ou envie uma imagem abaixo.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Upload direto</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
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
                <button class="btn btn-primary" type="submit" @disabled(!$instagramConnected)>Agendar</button>
            </div>
        </form>
    </div>
</div>
@endsection
