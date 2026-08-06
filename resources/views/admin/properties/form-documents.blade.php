@php
    $extractionLabels = [
        'pending' => 'Procesando…', 'done' => 'Listo para la IA',
        'unsupported' => 'Formato no compatible', 'failed' => 'No se pudo leer',
    ];
@endphp

<section class="form-card">
    <div class="form-card-heading">
        <div><h2>Documentos de referencia para la IA</h2><p>PDF o TXT (contratos, partidas SUNARP, ficha técnica). La IA los lee antes de redactar para no inventar datos.</p></div>
    </div>

    <form method="post" enctype="multipart/form-data"
        action="{{ route('admin.properties.documents.store', $record) }}" data-presentation-document-upload>
        @csrf
        <label class="upload-drop media-drop">
            <span class="material-symbols-rounded">description</span>
            <strong>Agregar documentos</strong>
            <small>PDF o TXT · hasta 15 MB cada uno</small>
            <input type="file" name="documents[]" multiple accept="application/pdf,text/plain" data-presentation-document-input>
        </label>
    </form>

    <div class="document-list">
        @forelse($record->documents as $document)
            <article class="document-row" data-presentation-document-row>
                <span class="material-symbols-rounded">draft</span>
                <div class="document-row-info">
                    <strong>{{ $document->original_name }}</strong>
                    <small>{{ number_format($document->size_bytes / 1024, 0) }} KB · {{ $extractionLabels[$document->extraction_status] ?? $document->extraction_status }}</small>
                </div>
                <form method="post" data-presentation-document-delete
                    action="{{ route('admin.properties.documents.destroy', [$record, $document]) }}">
                    @csrf @method('delete')
                    <button class="mini-button mini-button-danger" type="submit">Eliminar</button>
                </form>
            </article>
        @empty
            <p class="document-empty">Aún no hay documentos adjuntos.</p>
        @endforelse
    </div>
</section>
