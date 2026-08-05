@php
    $youtubeVideos = old('youtube_videos', $record->exists
        ? $record->youtubeVideos->map(fn ($video) => [
            'url' => $video->original_url, 'title' => $video->title,
        ])->all() : []);
@endphp

<section class="form-card" data-youtube-editor>
    <div class="form-card-heading">
        <div><h2>Videos de YouTube</h2><p>Agrega recorridos o presentaciones sin subir archivos pesados.</p></div>
        <button class="mini-button" type="button" data-youtube-add>+ Agregar video</button>
    </div>
    <div class="youtube-editor-list" data-youtube-list>
        @foreach($youtubeVideos as $index => $video)
            <div class="youtube-editor-row">
                <label class="field"><span>Enlace de YouTube</span><input type="url" name="youtube_videos[{{ $index }}][url]" value="{{ $video['url'] ?? '' }}" placeholder="https://youtu.be/..."></label>
                <label class="field"><span>Título opcional</span><input type="text" name="youtube_videos[{{ $index }}][title]" value="{{ $video['title'] ?? '' }}" maxlength="120" placeholder="Recorrido de la propiedad"></label>
                <button class="feature-remove" type="button" data-youtube-remove aria-label="Quitar video">×</button>
            </div>
        @endforeach
    </div>
    <div class="youtube-empty" data-youtube-empty @if(count($youtubeVideos)) hidden @endif>
        <span class="material-symbols-rounded">smart_display</span>
        <p>Aún no hay videos de YouTube.</p>
    </div>
    <template data-youtube-template>
        <div class="youtube-editor-row">
            <label class="field"><span>Enlace de YouTube</span><input type="url" name="youtube_videos[__INDEX__][url]" placeholder="https://youtu.be/..."></label>
            <label class="field"><span>Título opcional</span><input type="text" name="youtube_videos[__INDEX__][title]" maxlength="120" placeholder="Recorrido de la propiedad"></label>
            <button class="feature-remove" type="button" data-youtube-remove aria-label="Quitar video">×</button>
        </div>
    </template>
    <p class="media-help"><span class="material-symbols-rounded">verified_user</span> Se aceptan enlaces youtube.com, Shorts y youtu.be. La reproducción usa el dominio de privacidad mejorada de YouTube.</p>
</section>
