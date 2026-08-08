@if(!empty($intro))
    {{-- El texto llega con **negritas** al estilo markdown; se escapa primero y
         recién después se convierte, para que nunca entre HTML por esa vía. --}}
    <p class="section-intro">{!! preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', e($intro)) !!}</p>
@endif
