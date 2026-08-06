{{-- Clásico Azul y Oro — adaptado de storage/planitllas/Plantilla 1.HTM --}}
<style>
  {{-- dompdf (this version) does not render CSS gradients as backgrounds — a solid color avoids an invisible/white hero. --}}
  .p1-hero { background: {{ $theme['primary'] }}; color: {{ $theme['on_primary'] }}; padding: 12mm 14mm 10mm; }
  .p1-hero .brand { font-size: 8.5pt; letter-spacing: 2px; color: {{ $theme['accent'] }}; text-transform: uppercase; }
  .p1-hero h1 { font-size: {{ $titleSize }}; font-weight: 800; line-height: 1.15; margin-top: 5mm; }
  .p1-hero h1 span { color: {{ $theme['accent'] }}; }
  .p1-hero .sub { font-size: 11.5pt; color: #b8c4d4; margin-top: 3mm; line-height: 1.4; }
  .p1-heroimg { width: 100%; height: 90mm; display: block; }
  .p1-pricebar { background: {{ $theme['accent'] }}; color: {{ $theme['primary'] }}; display: table; width: 210mm; padding: 5mm 14mm; }
  .p1-pricebar .big { display: table-cell; vertical-align: middle; font-size: 18pt; font-weight: 800; }
  .p1-pricebar .per { display: table-cell; vertical-align: middle; font-size: 10pt; font-weight: 700; text-align: right; }
</style>
<div class="page">
  <div class="p1-hero">
    <div class="brand">{{ $agent['name'] }} · Ref. {{ $ref }}</div>
    <h1>{!! $title !!}</h1>
    <div class="sub">{{ $subtitle }}</div>
  </div>
  <img class="p1-heroimg" src="{{ $heroImage }}">
  <div class="p1-pricebar">
    <div class="big">{{ $priceMain }}</div>
    <div class="per">{{ $priceSub }}</div>
  </div>

  @if($hook)
    <div class="hook">{{ $hook }}</div>
  @endif

  @if(count($cards))
    <div class="cards">
      @foreach($cards as $card)
        <div class="card">
          <div class="t">{{ $card['title'] }}</div>
          <div class="d">{{ $card['description'] }}</div>
        </div>
      @endforeach
    </div>
  @endif

  @if($quote)
    <div class="quote">«{{ $quote }}»</div>
  @endif

  @include('brochures.partials.cover-footer', ['agent' => $agent])
</div>
