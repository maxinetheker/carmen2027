@extends('layouts.admin')

@section('title', $labelPlural)
@section('heading', $labelPlural)
@section('eyebrow', 'Gestión comercial')

@section('content')
@include('admin.partials.section-intro')
<div class="page-actions">
    <form class="table-search" method="get">
        <input name="q" value="{{ request('q') }}" placeholder="Buscar en {{ strtolower($labelPlural) }}...">
        <select name="per_page" aria-label="Registros por página" data-auto-submit>
            @foreach([12, 24, 48] as $amount)<option value="{{ $amount }}" @selected((int) request('per_page', 12) === $amount)>{{ $amount }} por página</option>@endforeach
        </select>
        <button>Buscar</button>
    </form>
    @if($route === 'properties')
        <button class="button button-ghost-dark" type="button" data-open-import>Importar desde enlace</button>
    @endif
    <a class="button button-accent" href="{{ route("admin.$route.create") }}">+ Nuevo {{ strtolower($label) }}</a>
</div>
<section class="data-card">
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr>
                @foreach($columns as $heading)<th>{{ $heading }}</th>@endforeach
                @if($route === 'properties')<th>Brochure</th>@endif
                <th><span class="sr-only">Acciones</span></th>
            </tr></thead>
            <tbody>
            @forelse($records as $record)
                <tr>
                    @foreach($columns as $field => $heading)
                        @php($value = data_get($record, $field))
                        <td data-label="{{ $heading }}">
                            @if(in_array($field, ['status', 'stage', 'priority', 'source', 'operation', 'follow_up_status', 'party_type', 'type']))
                                <span class="status-pill status-{{ $value }}">{{ \App\Support\CrmLabels::get($value) }}</span>
                            @elseif(in_array($field, ['price', 'value', 'budget']) && is_numeric($value))
                                <strong>US$ {{ number_format((float) $value) }}</strong>
                            @elseif($value instanceof \Carbon\CarbonInterface)
                                {{ $value->format('d M Y · H:i') }}
                            @elseif($field === 'probability')
                                <span class="progress-mini"><i style="width:{{ $value }}%"></i></span> {{ $value }}%
                            @else
                                {{ $value ?: '—' }}
                            @endif
                        </td>
                    @endforeach
                    @if($route === 'properties')
                        <td data-label="Material">
                            <button class="icon-button" type="button" data-open-presentation-dialog
                                data-panel-url="{{ route('admin.properties.presentations.panel', $record) }}"
                                title="Generar/ver presentación PDF">
                                <span class="material-symbols-rounded">description</span>
                            </button>
                            <button class="icon-button" type="button" data-open-social-dialog
                                data-panel-url="{{ route('admin.properties.social.panel', $record) }}"
                                title="Generar/ver imágenes para redes sociales">
                                <span class="material-symbols-rounded">imagesmode</span>
                            </button>
                        </td>
                    @endif
                    <td class="row-actions">
                        @if($route === 'leads' && $record->status !== 'qualified')
                            <form method="post" action="{{ route('admin.leads.convert', $record) }}">@csrf<button title="Convertir prospecto">◎</button></form>
                        @endif
                        <a href="{{ route("admin.$route.edit", $record) }}" title="Editar">Editar</a>
                        <form method="post" action="{{ route("admin.$route.destroy", $record) }}" data-confirm="¿Eliminar este registro?">
                            @csrf @method('delete')<button class="danger-link" title="Eliminar">×</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ count($columns) + ($route === 'properties' ? 2 : 1) }}"><div class="empty-state">Aún no hay registros. Crea el primero.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="table-footer">
        <span>Mostrando {{ $records->firstItem() ?? 0 }}–{{ $records->lastItem() ?? 0 }} de {{ $records->total() }}</span>
        {{ $records->links('vendor.pagination.admin') }}
    </div>
</section>

@if($route === 'properties')
    <dialog class="presentation-modal" data-presentation-dialog>
        <div data-dialog-body><p class="document-empty">Cargando…</p></div>
    </dialog>
    <dialog class="presentation-modal" data-social-dialog>
        <div data-social-body><p class="document-empty">Cargando…</p></div>
    </dialog>
    @include('admin.properties.import-modal')
@endif
@endsection
