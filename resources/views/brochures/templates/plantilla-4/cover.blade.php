{{-- Impacto con foto a página completa — adaptado de storage/planitllas/Plantilla 4.HTM --}}
<style>
  .p4-photoband { position: relative; height: 170mm; overflow: hidden; }
  .p4-photoband img { position: absolute; inset: 0; width: 100%; height: 100%; display: block; }
  {{-- Flat tinted overlay instead of a CSS gradient — dompdf (this version) does not render gradients,
       and a flat tint keeps text legible across the whole band regardless. --}}
  .p4-grad { position: absolute; inset: 0; background: rgba(4,10,20,0.58); }
  .p4-content { position: absolute; inset: 0; padding: 12mm 14mm; }
  .p4-brand { display: table; width: 182mm; font-size: 8.5pt; letter-spacing: 3px; text-transform: uppercase; color: {{ $theme['accent'] }}; }
  .p4-brand span { display: table-cell; }
  .p4-brand span:last-child { text-align: right; }
  .p4-badge { display: inline-block; background: #d81f26; color: #fff; font-size: 10pt; font-weight: 800; letter-spacing: 3px; padding: 2.2mm 5mm; margin-top: 8mm; }
  .p4-content h1 { font-size: {{ $titleSize }}; font-weight: 800; line-height: 1.05; margin-top: 5mm; color: #fff; }
  .p4-content h1 span { color: {{ $theme['accent'] }}; }
  .p4-content .sub { font-size: 11pt; color: #dbe4ee; margin-top: 3mm; }
  .p4-lower { padding: 6mm 14mm 0; }
  .p4-pricebar { display: table; width: 182mm; }
  .p4-pricebar .big { display: table-cell; vertical-align: baseline; font-size: 20pt; font-weight: 800; color: {{ $theme['accent'] }}; }
  .p4-pricebar .per { display: table-cell; vertical-align: baseline; text-align: right; font-size: 10.5pt; color: {{ $theme['text'] }}; }
</style>
<div class="page">
  <div class="p4-photoband">
    <img src="{{ $heroImage }}">
    <div class="p4-grad"></div>
    <div class="p4-content">
      <div class="p4-brand"><span>{{ $agent['name'] }}</span><span>Ref. {{ $ref }}</span></div>
      <div class="p4-badge">{{ $badge }}</div>
      <h1>{!! $title !!}</h1>
      <div class="sub">{{ $subtitle }}</div>
    </div>
  </div>

  <div class="p4-lower">
    <div class="p4-pricebar">
      <div class="big">{{ $priceMain }}</div>
      <div class="per">{{ $priceSub }}</div>
    </div>
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
