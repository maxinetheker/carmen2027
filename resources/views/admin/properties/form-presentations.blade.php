<section class="form-card" data-presentations-panel
    data-status-url="{{ route('admin.properties.presentations.status', [$record, '__ID__']) }}">
    <div class="form-card-heading">
        <div><h2>Presentaciones PDF</h2><p>Brochures generados con IA a partir de esta ficha.</p></div>
        <button class="mini-button" type="button" data-open-presentation-modal>+ Generar presentación PDF</button>
    </div>

    <div class="presentation-list" data-presentation-list>
        @forelse($record->presentations as $presentation)
            <article class="presentation-row" data-presentation-row data-presentation-id="{{ $presentation->id }}"
                @unless(in_array($presentation->status, ['queued', 'processing'])) data-poll-done @endunless>
                <span class="material-symbols-rounded">picture_as_pdf</span>
                <div class="presentation-row-info">
                    <strong>{{ $presentation->template_label }}</strong>
                    <small>
                        {{ $presentation->created_at->format('d/m/Y H:i') }} ·
                        <span class="status-pill status-{{ $presentation->status }}" data-presentation-status-label>{{ $presentation->status_label }}</span>
                    </small>
                    <small class="presentation-error" data-presentation-error @unless($presentation->status === 'failed') hidden @endif>{{ $presentation->error_message }}</small>
                </div>
                <div class="presentation-row-actions">
                    <a class="mini-button" data-presentation-preview href="{{ $presentation->pdf_url }}" target="_blank" rel="noopener" @unless($presentation->status === 'done') hidden @endif>Vista previa</a>
                    <form method="post" data-confirm="¿Eliminar esta presentación?"
                        action="{{ route('admin.properties.presentations.destroy', [$record, $presentation]) }}">
                        @csrf @method('delete')
                        <button class="mini-button mini-button-danger" type="submit">Eliminar</button>
                    </form>
                </div>
            </article>
        @empty
            <p class="document-empty" data-presentation-empty>Aún no generaste ninguna presentación.</p>
        @endforelse
    </div>

    <template data-presentation-row-template>
        <article class="presentation-row" data-presentation-row>
            <span class="material-symbols-rounded">picture_as_pdf</span>
            <div class="presentation-row-info">
                <strong></strong>
                <small>Generando… · <span class="status-pill status-queued" data-presentation-status-label>En cola</span></small>
                <small class="presentation-error" data-presentation-error hidden></small>
            </div>
            <div class="presentation-row-actions">
                <a class="mini-button" data-presentation-preview href="#" target="_blank" rel="noopener" hidden>Vista previa</a>
            </div>
        </article>
    </template>
</section>

@include('admin.properties.form-presentation-modal')
