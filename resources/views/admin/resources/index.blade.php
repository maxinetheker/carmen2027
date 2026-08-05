@extends('layouts.admin')

@section('title', $labelPlural)
@section('heading', $labelPlural)
@section('eyebrow', 'Gestión comercial')

@section('content')
<div class="page-actions">
    <form class="table-search" method="get">
        <input name="q" value="{{ request('q') }}" placeholder="Buscar en {{ strtolower($labelPlural) }}...">
        <select name="per_page" aria-label="Registros por página" data-auto-submit>
            @foreach([12, 24, 48] as $amount)<option value="{{ $amount }}" @selected((int) request('per_page', 12) === $amount)>{{ $amount }} por página</option>@endforeach
        </select>
        <button>Buscar</button>
    </form>
    <a class="button button-accent" href="{{ route("admin.$route.create") }}">+ Nuevo {{ strtolower($label) }}</a>
</div>
<section class="data-card">
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr>
                @foreach($columns as $heading)<th>{{ $heading }}</th>@endforeach
                <th><span class="sr-only">Acciones</span></th>
            </tr></thead>
            <tbody>
            @forelse($records as $record)
                <tr>
                    @foreach($columns as $field => $heading)
                        @php($value = data_get($record, $field))
                        <td data-label="{{ $heading }}">
                            @if(in_array($field, ['status', 'stage', 'priority', 'source', 'operation', 'follow_up_status']))
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
                <tr><td colspan="{{ count($columns) + 1 }}"><div class="empty-state">Aún no hay registros. Crea el primero.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="table-footer">
        <span>Mostrando {{ $records->firstItem() ?? 0 }}–{{ $records->lastItem() ?? 0 }} de {{ $records->total() }}</span>
        {{ $records->links('vendor.pagination.admin') }}
    </div>
</section>
@endsection
