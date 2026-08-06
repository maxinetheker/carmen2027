{{-- Minimalista blanco — adaptado de storage/planitllas/Plantilla 3.HTM --}}
<style>
  .p3-wrap { padding: 12mm 14mm 0; }
  .p3-brand { display: table; width: 182mm; font-size: 8.5pt; letter-spacing: 3px; text-transform: uppercase; color: {{ $theme['muted'] }}; border-bottom: 0.4mm solid #e3e6ea; padding-bottom: 4mm; }
  .p3-brand span { display: table-cell; }
  .p3-brand span:last-child { text-align: right; }
  .p3-wrap h1 { font-size: {{ $titleSize }}; font-weight: 300; line-height: 1.15; margin-top: 8mm; letter-spacing: -0.5px; color: {{ $theme['primary'] }}; }
  .p3-wrap h1 b { font-weight: 800; }
  .p3-sub { font-size: 11pt; color: {{ $theme['muted'] }}; margin-top: 4mm; }
  .p3-rule { width: 22mm; height: 1.2mm; background: {{ $theme['primary'] }}; margin: 6mm 0 0; }
  .p3-heroimg { width: 100%; height: 82mm; display: block; margin-top: 6mm; }
  .p3-price { padding: 6mm 14mm 0; font-size: 20pt; font-weight: 300; color: {{ $theme['primary'] }}; }
  .p3-price b { font-weight: 800; }
  .p3-price small { font-size: 10pt; color: {{ $theme['muted'] }}; display: block; margin-top: 1mm; }
</style>
<div class="page">
  <div class="p3-wrap">
    <div class="p3-brand"><span>{{ $agent['name'] }}</span><span>Ref. {{ $ref }}</span></div>
    <h1>{!! $title !!}</h1>
    <div class="p3-sub">{{ $subtitle }}</div>
    <div class="p3-rule"></div>
  </div>
  <img class="p3-heroimg" src="{{ $heroImage }}">
  <div class="p3-price"><b>{{ $priceMain }}</b><small>{{ $priceSub }}</small></div>

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
