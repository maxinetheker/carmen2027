{{-- expects: $heading, $gallery (array of [src, caption]), $specs, $trustParagraph, $stats, $steps, $agent --}}
<div class="page">
  <div class="content-head">
    <h2>{!! $heading !!}</h2>
  </div>

  @if(count($gallery))
    <div class="gal">
      @foreach($gallery as $item)
        <figure>
          <img src="{{ $item['src'] }}">
          @if(!empty($item['caption']))
            <figcaption>{{ $item['caption'] }}</figcaption>
          @endif
        </figure>
      @endforeach
    </div>
  @endif

  {{-- Real property data (features already entered by the advisor) — always available
       regardless of what the AI toggles are set to, so this page never looks empty. --}}
  @if(count($specs))
    <div class="stats">
      @foreach($specs as $spec)
        <div class="stat">
          <div class="n">{{ $spec['value'] }}</div>
          <div class="d">{{ $spec['label'] }}</div>
        </div>
      @endforeach
    </div>
  @endif

  @if(!empty($trustParagraph))
    <div class="trust">{!! $trustParagraph !!}</div>
  @endif

  @if(count($stats))
    <div class="stats">
      @foreach($stats as $stat)
        <div class="stat">
          <div class="n">{{ $stat['value'] }}</div>
          <div class="d">{{ $stat['label'] }}</div>
        </div>
      @endforeach
    </div>
  @endif

  <div class="steps">
    @foreach($steps as $i => $step)
      <div class="step">
        <div class="num">{{ $i + 1 }}</div>
        <div class="t">{{ $step['t'] }}</div>
        <div class="d">{{ $step['d'] }}</div>
      </div>
    @endforeach
  </div>

  @include('brochures.partials.cta-footer', ['agent' => $agent, 'logo' => $logo, 'ref' => $ref])
</div>
