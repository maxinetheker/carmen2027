{{-- Fetched via AJAX into the social-image dialog on the properties list. Same two-view
     structure as the presentation panel: saved images, and the generation form. --}}
<button class="modal-close" type="button" data-close-social-modal aria-label="Cerrar">×</button>
<h2>{{ $record->title }}</h2>

<div data-panel-view="list">
    <div class="social-gallery" data-social-list
        data-status-url="{{ route('admin.properties.social.status', [$record, '__ID__']) }}">
        @forelse($record->socialImages as $image)
            <article class="social-card" data-social-row data-social-id="{{ $image->id }}"
                @unless(in_array($image->status, ['queued', 'processing'])) data-poll-done @endunless>
                <a class="social-thumb" data-social-preview href="{{ $image->image_url }}" target="_blank" rel="noopener"
                    @unless($image->status === 'done') hidden @endif>
                    <img src="{{ $image->image_url }}" alt="Imagen para redes de {{ $record->title }}">
                </a>
                <div class="social-card-info">
                    <strong>{{ $image->format_label }}</strong>
                    <small>
                        {{ $image->created_at->format('d/m/Y H:i') }} ·
                        <span class="status-pill status-{{ $image->status }}" data-social-status-label>{{ $image->status_label }}</span>
                    </small>
                    <small class="presentation-error" data-social-error @unless($image->status === 'failed') hidden @endif>{{ $image->error_message }}</small>
                    <small class="presentation-warning" data-social-warning @unless($image->warnings) hidden @endunless>{{ implode(' ', $image->warnings) }}</small>
                </div>
                <div class="social-card-actions">
                    <a class="mini-button" data-social-download href="{{ $image->image_url }}" download
                        @unless($image->status === 'done') hidden @endif>Descargar</a>
                    <form method="post" data-confirm="¿Eliminar esta imagen?"
                        action="{{ route('admin.properties.social.destroy', [$record, $image]) }}">
                        @csrf @method('delete')
                        <button class="mini-button mini-button-danger" type="submit">Eliminar</button>
                    </form>
                </div>
            </article>
        @empty
            <p class="document-empty" data-social-empty>Aún no generaste ninguna imagen para redes.</p>
        @endforelse
    </div>

    <template data-social-row-template>
        <article class="social-card" data-social-row>
            <a class="social-thumb" data-social-preview href="#" target="_blank" rel="noopener" hidden>
                <img src="" alt="">
            </a>
            <div class="social-card-info">
                <strong></strong>
                <small>Generando… · <span class="status-pill status-queued" data-social-status-label>En cola</span></small>
                <small class="presentation-error" data-social-error hidden></small>
                <small class="presentation-warning" data-social-warning hidden></small>
            </div>
            <div class="social-card-actions">
                <a class="mini-button" data-social-download href="#" download hidden>Descargar</a>
            </div>
        </article>
    </template>

    <div class="modal-actions">
        <button class="button button-accent" type="button" data-show-social-form>+ Generar imagen para redes</button>
    </div>
</div>

<div data-panel-view="form" hidden>
    <button class="text-link" type="button" data-show-social-list>← Volver a las imágenes</button>
    @include('admin.properties.form-social-modal')
</div>
