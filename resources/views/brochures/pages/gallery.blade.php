{{-- expects: $heading, $rows ([[src]]), $imageHeight, $agent, $logo, $ref --}}
<div class="page">
  <div class="content-head">
    <h2>{!! $heading !!}</h2>
  </div>

  <div class="photo-sheet">
    @foreach($rows as $row)
      <div class="photo-sheet-row">
        @foreach($row as $item)
          <figure><img src="{{ $item['src'] }}" style="height: {{ $imageHeight }}mm"></figure>
        @endforeach
      </div>
    @endforeach
  </div>

  @include('brochures.partials.cta-footer', ['agent' => $agent, 'logo' => $logo, 'ref' => $ref])
</div>
