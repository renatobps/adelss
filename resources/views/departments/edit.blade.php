@extends('layouts.porto')
@section('title', 'Editar departamento — '.$department->name)
@section('page-title', 'Configurações do departamento')
@section('breadcrumbs')
    <li><a href="{{ route('departments.index') }}">Departamentos</a></li>
    <li><a href="{{ route('departments.show', $department) }}">{{ $department->name }}</a></li>
    <li><span>Configurações</span></li>
@endsection
@section('content')
    @include('departments.partials.form')
@endsection
