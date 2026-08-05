@php
    $galleryItems = $property->media->sortByDesc('is_cover')->map(fn ($media) => [
        'type' => $media->type, 'url' => $media->url, 'title' => null,
        'narrow' => $media->type === 'image' && $media->width && $media->height
            && ($media->width / $media->height) < .82,
    ])->values();
    foreach ($property->youtubeVideos as $video) {
        $galleryItems->push([
            'type' => 'youtube', 'url' => $video->embed_url,
            'thumbnail' => $video->thumbnail_url, 'title' => $video->title,
        ]);
    }
    if ($galleryItems->isEmpty()) {
        $galleryItems->push(['type' => 'image', 'url' => $property->cover_url, 'title' => null]);
    }
@endphp

<section class="detail-gallery" data-property-gallery>
    <div class="gallery-stage">
        @foreach($galleryItems as $index => $item)
            <div class="gallery-panel" data-gallery-panel @if($index) hidden @endif>
                @if($item['type'] === 'image')
                    @if($item['narrow'] ?? false)
                        <img class="gallery-image-backdrop" src="{{ $item['url'] }}" alt="" aria-hidden="true">
                    @endif
                    <img class="gallery-image-main" src="{{ $item['url'] }}" alt="{{ $property->title }} · imagen {{ $index + 1 }}">
                @elseif($item['type'] === 'youtube')
                    <iframe src="{{ $item['url'] }}" title="{{ $item['title'] ?: 'Video de '.$property->title }}"
                        loading="lazy" referrerpolicy="strict-origin-when-cross-origin"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen></iframe>
                @else
                    <video src="{{ $item['url'] }}" controls preload="metadata" playsinline>Tu navegador no puede reproducir este video.</video>
                @endif
            </div>
        @endforeach
        <span class="gallery-badge">{{ $property->type_label }} seleccionado</span>
    </div>
    @if($galleryItems->count() > 1)
        <div class="gallery-thumbs" role="tablist" aria-label="Galería de la propiedad">
            @foreach($galleryItems as $index => $item)
                <button type="button" data-gallery-target="{{ $index }}" role="tab"
                    aria-selected="{{ $index === 0 ? 'true' : 'false' }}" class="{{ $index === 0 ? 'active' : '' }}">
                    @if($item['type'] === 'image')
                        <img src="{{ $item['url'] }}" alt="">
                    @elseif($item['type'] === 'youtube')
                        <img src="{{ $item['thumbnail'] }}" alt=""><span class="youtube-thumb-icon material-symbols-rounded">play_circle</span>
                    @else
                        <span class="material-symbols-rounded">play_circle</span>
                    @endif
                </button>
            @endforeach
        </div>
    @endif
</section>
