@extends('layouts.admin')

@section('title', 'Exportación')
@section('heading', 'Exportar a Excel')
@section('eyebrow', 'Informes')

@section('content')
<div class="export-grid">
    <section class="form-card export-card">
        <div class="form-card-heading">
            <div>
                <h2>Resumen semanal</h2>
                <p>El mismo cuadro que llevabas a mano: compradores, vendedores, visitas agendadas, tareas y a quién toca contactar, cada bloque con su color.</p>
            </div>
            <span>Recomendado</span>
        </div>
        <ul class="export-preview">
            <li><i style="background:#b4c6e7"></i> Clientes compradores <strong>{{ $counts['buyers'] }}</strong></li>
            <li><i style="background:#ffd966"></i> Clientes vendedores y captaciones <strong>{{ $counts['sellers'] }}</strong></li>
            <li><i style="background:#c6e0b4"></i> Visitas y citas de la semana <strong>{{ $counts['visits'] }}</strong></li>
            <li><i style="background:#f8cbad"></i> Tareas pendientes <strong>{{ $counts['tasks'] }}</strong></li>
            <li><i style="background:#d9d9d9"></i> Por contactar (carteo y seguimiento)</li>
        </ul>
        <form class="export-form" method="get" action="{{ route('admin.exports.weekly') }}">
            <label class="field"><span>Desde</span><input type="date" name="from" value="{{ $from }}" required></label>
            <label class="field"><span>Hasta</span><input type="date" name="to" value="{{ $to }}" required></label>
            <button class="button button-accent" type="submit">Descargar resumen</button>
        </form>
    </section>

    <section class="form-card export-card">
        <div class="form-card-heading">
            <div>
                <h2>Datos completos</h2>
                <p>Una hoja por sección, con todos los campos. Útil para respaldos o para analizar la cartera con filtros y tablas dinámicas.</p>
            </div>
            <span>Detallado</span>
        </div>
        <form class="export-form export-form-block" method="get" action="{{ route('admin.exports.data') }}">
            <fieldset class="export-sections">
                <legend>¿Qué hojas quieres incluir?</legend>
                @foreach($sections as $key => $label)
                    <label class="checkbox-inline">
                        <input type="checkbox" name="sections[]" value="{{ $key }}" checked>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </fieldset>
            <button class="button button-accent" type="submit">Descargar datos</button>
        </form>
    </section>
</div>
@endsection
