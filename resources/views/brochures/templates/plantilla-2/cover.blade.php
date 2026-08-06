{{-- Marca RE/MAX azul y rojo — adaptado de storage/planitllas/Plantilla 2.HTM --}}
<style>
  {{-- Two solid-color table cells instead of a CSS gradient — dompdf (this version)
       renders neither gradients nor flexbox (display:flex just hides the element). --}}
  .p2-topstripe { height: 4mm; display: table; width: 210mm; table-layout: fixed; }
  .p2-topstripe span { display: table-cell; }
  .p2-topstripe .a { background: {{ $theme['primary'] }}; }
  .p2-topstripe .b { background: {{ $theme['accent'] }}; }
  .p2-hero { padding: 10mm 14mm 7mm; border-bottom: 1mm solid {{ $theme['accent'] }}; }
  .p2-hero .brand { display: table; width: 182mm; font-size: 9pt; color: {{ $theme['primary'] }}; font-weight: 800; }
  .p2-hero .brand span { display: table-cell; vertical-align: middle; }
  .p2-hero .ref { font-size: 9pt; color: {{ $theme['muted'] }}; font-weight: 400; text-align: right; }
  .p2-hero h1 { font-size: {{ $titleSize }}; font-weight: 800; color: {{ $theme['primary'] }}; line-height: 1.15; margin-top: 5mm; }
  .p2-hero h1 span { color: {{ $theme['accent'] }}; }
  .p2-hero .sub { font-size: 11pt; color: {{ $theme['text'] }}; margin-top: 3mm; }
  .p2-heroimg { width: 100%; height: 90mm; display: block; }
  .p2-pricebar { display: table; width: 210mm; }
  .p2-pricebar .price { display: table-cell; vertical-align: middle; width: 55%; background: {{ $theme['primary'] }}; color: #fff; padding: 5mm 14mm; font-size: 18pt; font-weight: 800; }
  .p2-pricebar .per { display: table-cell; vertical-align: middle; width: 45%; background: {{ $theme['accent'] }}; color: #fff; padding: 5mm 10mm; font-size: 10pt; font-weight: 700; }
</style>
<div class="page">
  <div class="p2-topstripe"><span class="a"></span><span class="b"></span></div>
  <div class="p2-hero">
    <div class="brand"><span>{{ $agent['name'] }}</span><span class="ref">Ref. {{ $ref }}</span></div>
    <h1>{!! $title !!}</h1>
    <div class="sub">{{ $subtitle }}</div>
  </div>
  <img class="p2-heroimg" src="{{ $heroImage }}">
  <div class="p2-pricebar">
    <div class="price">{{ $priceMain }}</div>
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
