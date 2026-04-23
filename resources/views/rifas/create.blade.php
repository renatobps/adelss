@extends('layouts.porto')

@section('title', 'Nova Rifa')
@section('page-title', 'Nova Rifa')

@section('breadcrumbs')
    <li><a href="{{ route('rifas.index') }}">Rifas</a></li>
    <li><span>Nova</span></li>
@endsection

@section('content')
<section class="card">
    <header class="card-header">
        <h2 class="card-title">Cadastro de rifa</h2>
    </header>
    <div class="card-body">
        <form method="POST" action="{{ route('rifas.store') }}">
            @csrf
            @include('rifas.partials.form')
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit">Salvar rifa</button>
                <a href="{{ route('rifas.index') }}" class="btn btn-default">Cancelar</a>
            </div>
        </form>
    </div>
</section>
@endsection
