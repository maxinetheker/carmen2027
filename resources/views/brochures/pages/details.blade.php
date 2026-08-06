{{-- expects: $heading, $croquisSvg (nullable sanitized <svg>), $planoImage, $faqs, $ficha, $description, $agent --}}
<div class="page {{ $croquisSvg ? 'details-compact' : 'details-spacious' }}">
  <div class="content-head">
    <h2>{!! $heading !!}</h2>
  </div>

  @if($croquisSvg)
    <div class="croquis">
      <figure class="map">
        {!! $croquisSvg !!}
        <figcaption>Croquis referencial de ubicación, sin escala</figcaption>
      </figure>
      @if($planoImage)
        <figure class="plano">
          <img src="{{ $planoImage }}">
          <figcaption>Vista de la propiedad</figcaption>
        </figure>
      @endif
    </div>
  @endif

  @if($description)
    <div class="property-summary"><strong>La propiedad</strong><p>{{ $description }}</p></div>
  @endif

  @if(count($ficha))
    <table class="ficha">
      @foreach($ficha as $row)
        <tr><td>{{ $row['label'] }}</td><td>{{ $row['value'] }}</td></tr>
      @endforeach
    </table>
  @endif

  @if(count($faqs))
    <div class="faq">
      <h3>Preguntas frecuentes</h3>
      @foreach($faqs as $faq)
        <p><b>{{ $faq['question'] }}</b> {{ $faq['answer'] }}</p>
      @endforeach
    </div>
  @endif

  @include('brochures.partials.cta-footer', ['agent' => $agent, 'logo' => $logo, 'ref' => $ref])
</div>
