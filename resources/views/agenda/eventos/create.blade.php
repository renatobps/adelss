@extends('layouts.porto')

@section('title', 'Novo evento')

@section('page-title', 'Novo evento')

@section('breadcrumbs')
    <li><a href="{{ route('dashboard') }}">Visão Geral</a></li>
    <li><span>Agenda</span></li>
    <li><a href="{{ route('agenda.eventos.index') }}">Eventos</a></li>
    <li><span>Novo</span></li>
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
<form method="post" action="{{ route('agenda.eventos.store') }}" enctype="multipart/form-data">
    @csrf
    @include('agenda.eventos.partials.form', ['event' => null, 'categories' => $categories])

    <div class="text-center mb-5">
        <button type="submit" class="btn btn-primary btn-lg px-5"><i class="bx bx-check"></i> Salvar</button>
        <a href="{{ route('agenda.eventos.index') }}" class="btn btn-default ms-2">Cancelar</a>
    </div>
</form>
@endsection
