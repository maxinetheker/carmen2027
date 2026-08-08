{{-- Explicación de la pantalla detrás de un botón «i» junto al título: el texto
     completo se lee cuando hace falta y deja de robar espacio arriba en cada visita.
     Sale de $intro, que define cada controlador. --}}
@php($infoText = trim($intro ?? ''))
@if($infoText !== '')
    <button class="info-button" type="button" data-info-open
        aria-label="Qué es esta sección" title="Qué es esta sección">i</button>
    <dialog class="info-modal" data-info-dialog>
        <h2>@yield('heading', 'Sobre esta sección')</h2>
        {{-- El texto llega con **negritas** al estilo markdown; se escapa primero y
             recién después se convierte, para que nunca entre HTML por esa vía. --}}
        <p>{!! preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', e($infoText)) !!}</p>
        <form method="dialog">
            <button class="button button-accent" type="submit">Entendido</button>
        </form>
    </dialog>
@endif
