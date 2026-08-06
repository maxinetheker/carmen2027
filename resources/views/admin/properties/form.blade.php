@extends('layouts.admin')

@section('title', ($record->exists ? 'Editar ' : 'Nueva ').'propiedad')
@section('heading', $record->exists ? 'Editar propiedad' : 'Nueva propiedad')
@section('eyebrow', 'Inventario inmobiliario')

@php
    $features = old('features', $record->exists
        ? $record->features->map(fn ($item) => $item->only('icon', 'label', 'value'))->all()
        : [['icon' => 'info', 'label' => '', 'value' => '']]);
    $description = app(\App\Support\RichTextSanitizer::class)
        ->clean(old('description', $record->description));
@endphp

@section('content')
<form class="resource-form property-form" enctype="multipart/form-data" method="post"
    data-dirty-form
    action="{{ $record->exists ? route("admin.$route.update", $record) : route("admin.$route.store") }}">
    @csrf
    @if($record->exists) @method('put') @endif

    @if($errors->any())
        <div class="form-error"><strong>Revisa la información:</strong> {{ $errors->first() }}</div>
    @endif

    @include('admin.properties.form-basic')
    @include('admin.properties.form-location')
    @include('admin.properties.form-description')
    @include('admin.properties.form-media')
    @include('admin.properties.form-youtube')
    @include('admin.properties.form-features')

    <div class="form-actions">
        <a class="button button-ghost-dark" href="{{ route("admin.$route.index") }}">Cancelar</a>
    </div>
    <div class="fixed-save-bar" data-save-bar hidden>
        <span><strong>Cambios sin guardar</strong><small>La ficha fue modificada.</small></span>
        <div class="fixed-save-actions">
            <button class="button undo-changes-button" type="button" data-undo-changes>Deshacer cambios</button>
            <button class="button button-accent" type="submit" data-save-button>Guardar propiedad</button>
        </div>
    </div>
</form>

@if($record->exists)
    @include('admin.properties.form-documents')
    @include('admin.properties.form-presentations')
@endif
@endsection
