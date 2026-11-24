@extends('layouts.porto')
@section('title', $event->exists ? 'Editar evento' : 'Novo evento')
@section('page-title', $event->exists ? 'Editar evento' : 'Novo evento')
@section('breadcrumbs')
    <li><a href="{{ route('departments.index') }}">Departamentos</a></li>
    <li><a href="{{ route('departments.events.index', $department) }}">{{ $department->name }}</a></li>
    <li><span>{{ $event->exists ? 'Editar evento' : 'Novo evento' }}</span></li>
@endsection
@section('content')
<section class="card">
    <header class="card-header"><h2 class="card-title">Evento de {{ $department->name }}</h2></header>
    <form method="POST" enctype="multipart/form-data" action="{{ $event->exists ? route('departments.events.update', [$department, $event]) : route('departments.events.store', $department) }}">
        @csrf
        @if($event->exists) @method('PUT') @endif
        <div class="card-body">
            <div class="mb-3">
                <label for="title" class="form-label">Nome do evento *</label>
                <input id="title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $event->title) }}" required maxlength="191">
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="start_date" class="form-label">Início *</label>
                    <input id="start_date" name="start_date" type="datetime-local" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', $event->start_date?->format('Y-m-d\TH:i')) }}" required>
                    @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label for="end_date" class="form-label">Término</label>
                    <input id="end_date" name="end_date" type="datetime-local" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', $event->end_date?->format('Y-m-d\TH:i')) }}">
                    @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="form-check mb-3">
                <input type="hidden" name="all_day" value="0">
                <input id="all_day" name="all_day" type="checkbox" value="1" class="form-check-input" @checked(old('all_day', $event->all_day)) aria-describedby="all-day-help">
                <label for="all_day" class="form-check-label">Dia inteiro</label>
                <div id="all-day-help" class="form-text">Para eventos de dia inteiro, os horários serão ignorados e a data de término será incluída.</div>
            </div>
            <div class="mb-3">
                <label for="location" class="form-label">Local</label>
                <input id="location" name="location" class="form-control @error('location') is-invalid @enderror" value="{{ old('location', $event->location) }}" maxlength="191">
                @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Descrição</label>
                <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="5" maxlength="10000">{{ old('description', $event->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-4">
                <div class="col-12 col-lg-5">
                    <label for="cost" class="form-label">Custo do evento</label>
                    <div class="input-group has-validation">
                        <span class="input-group-text">R$</span>
                        <input id="cost" name="cost" type="text" inputmode="decimal" class="form-control @error('cost') is-invalid @enderror" value="{{ old('cost', $event->cost !== null ? number_format((float) $event->cost, 2, ',', '.') : '') }}" placeholder="0,00" maxlength="17" aria-describedby="cost-help">
                        @error('cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div id="cost-help" class="form-text">Informe o custo total em reais. Deixe em branco se ainda não foi definido.</div>
                </div>
                <div class="col-12 col-lg-7" data-event-banner>
                    <label for="banner_image" class="form-label">Banner do evento</label>
                    <input id="banner_image" name="banner_image" type="file" accept="image/jpeg,image/png,image/webp" class="form-control @error('banner_image') is-invalid @enderror" aria-describedby="banner-help">
                    @error('banner_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div id="banner-help" class="form-text">JPG, PNG ou WebP, até 5 MB. Ao enviar uma nova imagem, ela substituirá o banner atual.</div>
                    <p class="text-danger small mt-2" data-banner-error role="alert" hidden></p>
                    <figure class="mt-3 mb-0" data-banner-preview @if(!$event->banner_url) hidden @endif>
                        <img @if($event->banner_url) src="{{ $event->banner_url }}" @endif data-original="{{ $event->banner_url }}" alt="Prévia do banner do evento" class="img-fluid rounded border" style="max-height:280px;object-fit:contain">
                        <figcaption class="text-muted small mt-1" data-banner-caption>Banner atual</figcaption>
                    </figure>
                    @if($event->banner_image)
                        <div class="form-check mt-3">
                            <input id="remove_banner" name="remove_banner" value="1" type="checkbox" class="form-check-input" @checked(old('remove_banner'))>
                            <label for="remove_banner" class="form-check-label">Remover banner atual</label>
                        </div>
                    @endif
                    @error('remove_banner')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
        <footer class="card-footer d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary" style="min-height:44px">Salvar evento</button>
            <a href="{{ route('departments.events.index', [$department, 'year' => $event->start_date->year]) }}" class="btn btn-default" style="min-height:44px">Cancelar</a>
        </footer>
    </form>
</section>
@endsection
@push('scripts')
<script src="{{ asset('js/department-event-form.js') }}?v={{ filemtime(public_path('js/department-event-form.js')) }}" defer></script>
@endpush
