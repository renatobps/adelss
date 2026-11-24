@extends('layouts.porto')
@section('title', 'Novo departamento')
@section('page-title', 'Novo departamento')
@section('breadcrumbs')
    <li><a href="{{ route('departments.index') }}">Departamentos</a></li>
    <li><span>Novo departamento</span></li>
@endsection
@section('content')
    @include('departments.partials.form', ['department' => new \App\Models\Department()])
@endsection
