@extends('layouts.porto')

@section('title', 'Editar evento')

@section('page-title', 'Editar evento')

@section('breadcrumbs')
    <li><a href="{{ route('dashboard') }}">Visão Geral</a></li>
    <li><span>Agenda</span></li>
    <li><a href="{{ route('agenda.eventos.index') }}">Eventos</a></li>
    <li><span>Editar</span></li>
@endsection

@section('content')
@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Corrija os seguintes pontos:</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
@if($event->public_slug)
    <div class="alert alert-info">
        <strong>Link público:</strong>
        <a href="{{ route('events.public.show', $event->public_slug) }}" target="_blank">{{ url('/evento/'.$event->public_slug) }}</a>
    </div>
@endif

<form method="post" action="{{ route('agenda.eventos.update', $event) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('agenda.eventos.partials.form', ['event' => $event, 'categories' => $categories])

    <div class="text-center mb-5">
        <button type="submit" class="btn btn-primary btn-lg px-5"><i class="bx bx-check"></i> Salvar</button>
        <a href="{{ route('agenda.eventos.index') }}" class="btn btn-default ms-2">Voltar</a>
    </div>
</form>
@endsection
