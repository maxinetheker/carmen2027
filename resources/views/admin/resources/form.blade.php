@extends('layouts.admin')

@section('title', ($record->exists ? 'Editar ' : 'Nuevo ').$label)
@section('heading', ($record->exists ? 'Editar ' : 'Nuevo ').strtolower($label))
@section('eyebrow', 'Gestión comercial')

@section('content')
<form class="resource-form" method="post" data-dirty-form action="{{ $record->exists ? route("admin.$route.update", $record) : route("admin.$route.store") }}">
    @csrf
    @if($record->exists) @method('put') @endif
    <div class="form-card">
        <div class="form-card-heading">
            <div><h2>Información del {{ strtolower($label) }}</h2><p>Completa los datos y guarda los cambios.</p></div>
            <div class="form-card-tools">
                @foreach($panels ?? [] as $panelView => $panelLabel)
                    <button class="button button-ghost-dark" type="button"
                        data-panel-open="{{ Str::afterLast($panelView, '.') }}">{{ $panelLabel }}</button>
                @endforeach
                <span>{{ $record->exists ? '#'.$record->id : 'Nuevo registro' }}</span>
            </div>
        </div>
        @if($errors->any())
            <div class="form-error"><strong>Revisa la información:</strong> {{ $errors->first() }}</div>
        @endif
        <div class="form-grid">
            @foreach($fields as $field)
                @php
                    $name = $field['name'];
                    $type = $field['type'] ?? 'text';
                    $value = old($name, data_get($record, $name) ?? ($field['default'] ?? null));
                    if ($value instanceof \Carbon\CarbonInterface) {
                        $value = $type === 'date' ? $value->format('Y-m-d') : $value->format('Y-m-d\TH:i');
                    }
                @endphp
                <label class="field @if($field['wide'] ?? false) field-wide @endif @if($type === 'checkbox') checkbox-field @endif">
                    @if($type === 'checkbox')
                        {{-- `?? true`: en un registro nuevo el atributo todavía no
                             existe y el valor por defecto de la columna es activo. --}}
                        <input type="checkbox" name="{{ $name }}" value="1"
                            @checked(old($name, $record->{$name} ?? ($field['default'] ?? true)))>
                        <span>{{ $field['label'] }}</span>
                        @if($field['help'] ?? false)<small class="field-help">{{ $field['help'] }}</small>@endif
                    @else
                        <span>{{ $field['label'] }}</span>
                        @if($type === 'textarea')
                            <textarea name="{{ $name }}" rows="4">{{ $value }}</textarea>
                        @elseif($type === 'select')
                            <select name="{{ $name }}"
                                @if($field['searchable'] ?? false) data-searchable-select @endif
                                @if($field['depends_on'] ?? false) data-depends-on="{{ $field['depends_on'] }}" @endif>
                                <option value="">Seleccionar</option>
                                @if($field['grouped_options'] ?? false)
                                    @php($selectedGroup = old($field['depends_on'], data_get($record, $field['depends_on'])))
                                    @foreach($field['grouped_options'] as $group => $options)
                                        @foreach($options as $optionValue => $optionLabel)
                                            <option value="{{ $optionValue }}" data-option-group="{{ $group }}"
                                                @selected((string) $value === (string) $optionValue && $selectedGroup === $group)>{{ $optionLabel }}</option>
                                        @endforeach
                                    @endforeach
                                @else
                                    @foreach($field['options'] ?? [] as $optionValue => $optionLabel)
                                        <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                                    @endforeach
                                @endif
                            </select>
                        @else
                            <input type="{{ $type }}" name="{{ $name }}" value="{{ $value }}" step="{{ $field['step'] ?? '' }}">
                        @endif
                        @if($field['help'] ?? false)<small class="field-help">{{ $field['help'] }}</small>@endif
                    @endif
                </label>
            @endforeach
        </div>
    </div>
    <div class="form-actions">
        <a class="button button-ghost-dark" href="{{ route("admin.$route.index") }}">Cancelar</a>
        <button class="button button-accent" type="submit">Guardar cambios</button>
    </div>
    <div class="fixed-save-bar mobile-record-save" data-save-bar @if(!$errors->any()) hidden @endif>
        <span><strong>Cambios sin guardar</strong><small>Puedes guardar desde cualquier parte.</small></span>
        <button class="button button-accent" type="submit" data-save-button>Guardar</button>
    </div>
</form>
{{-- Fuera del formulario principal: los paneles traen sus propios <form> y el HTML
     no permite anidarlos. Van en un modal para no alargar la ficha; el botón que
     los abre está arriba, junto al número del registro. --}}
@foreach($panels ?? [] as $panelView => $panelLabel)
    @php($panelKey = Str::afterLast($panelView, '.'))
    {{-- Se reabre solo cuando el panel acaba de guardar algo o cuando su propia
         validación falló, para no dejar al usuario mirando la ficha sin contexto. --}}
    <dialog class="panel-modal" data-panel-dialog="{{ $panelKey }}"
        @if(session('openPanel') === $panelKey || $errors->hasBag($panelKey)) data-panel-autoopen @endif>
        <form method="dialog" class="panel-modal-close">
            <button type="submit" aria-label="Cerrar">×</button>
        </form>
        @include($panelView)
    </dialog>
@endforeach
@endsection
