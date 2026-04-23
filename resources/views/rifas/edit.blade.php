@extends('layouts.porto')

@section('title', 'Editar Rifa')
@section('page-title', 'Editar Rifa')

@section('breadcrumbs')
    <li><a href="{{ route('rifas.index') }}">Rifas</a></li>
    <li><a href="{{ route('rifas.show', $rifa) }}">{{ $rifa->nome }}</a></li>
    <li><span>Editar</span></li>
@endsection

@section('content')
<section class="card">
    <header class="card-header">
        <h2 class="card-title">Editar dados da rifa</h2>
    </header>
    <div class="card-body">
        <form method="POST" action="{{ route('rifas.update', $rifa) }}">
            @csrf
            @method('PUT')
            @include('rifas.partials.form', ['rifa' => $rifa])
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit">Atualizar</button>
                <a href="{{ route('rifas.show', $rifa) }}" class="btn btn-default">Voltar</a>
            </div>
        </form>
    </div>
</section>
@endsection
