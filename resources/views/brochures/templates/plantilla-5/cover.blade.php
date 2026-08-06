{{-- Elegante crema y serif — adaptado de storage/planitllas/Plantilla 5.HTM --}}
<style>
  .p5-wrap { padding: 14mm 16mm 0; position: relative; }
  .p5-frame { position: absolute; top: 8mm; left: 8mm; right: 8mm; height: 130mm; border: 0.5mm solid {{ $theme['muted'] }}; pointer-events: none; }
  .p5-brand { text-align: center; font-size: 9pt; letter-spacing: 4px; text-transform: uppercase; color: {{ $theme['muted'] }}; }
  .p5-brand b { color: {{ $theme['primary'] }}; }
  .p5-wrap h1 { text-align: center; font-size: {{ $titleSize }}; font-weight: 700; line-height: 1.2; margin-top: 8mm; color: {{ $theme['primary'] }}; }
  .p5-wrap h1 em { font-style: italic; color: {{ $theme['accent'] }}; }
  .p5-sub { text-align: center; font-size: 11pt; color: {{ $theme['muted'] }}; margin-top: 3mm; font-style: italic; }
  .p5-orn { text-align: center; color: {{ $theme['muted'] }}; font-size: 13pt; margin: 5mm 0; letter-spacing: 6px; }
  {{-- box-shadow isn't rendered by dompdf (this version); the white border alone still reads as a framed photo. --}}
  .p5-heroimg { width: 100%; height: 82mm; display: block; border: 1.5mm solid #fff; }
  .p5-price { text-align: center; margin-top: 7mm; font-size: 20pt; font-weight: 700; color: {{ $theme['accent'] }}; }
  .p5-price small { display: block; font-size: 9.5pt; color: {{ $theme['muted'] }}; font-weight: 400; margin-top: 1.5mm; font-style: italic; }
</style>
<div class="page">
  <div class="p5-wrap">
    <div class="p5-frame"></div>
    <div class="p5-brand"><b>{{ $agent['name'] }}</b> · Ref. {{ $ref }}</div>
    <h1>{!! $title !!}</h1>
    <div class="p5-sub">{{ $subtitle }}</div>
    <div class="p5-orn">— ◆ —</div>
    <img class="p5-heroimg" src="{{ $heroImage }}">
    <div class="p5-price">{{ $priceMain }}<small>{{ $priceSub }}</small></div>
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

  @include('brochures.partials.cover-footer', ['agent' => $agent, 'logo' => $logo])
</div>
