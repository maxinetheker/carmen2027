{{-- expects $agent: name, role, address, phone, email, website; $logo: nullable data URI; $ref: nullable property code --}}
@php
    // Sizes follow the content so edits to the agency's details never reflow the panel:
    // a longer name or e-mail shrinks to fit instead of wrapping into the next line.
    // The panel is bounded and clipped regardless, so this is about looks, not safety.
    use App\Services\Brochure\TextFit;

    $nameColumn = empty($logo) ? 112 : 82;
    $nameSize = TextFit::toWidth($agent['name'], $nameColumn, max: 12, min: 8.5);
    $contactLine = collect([$agent['email'] ?? null, trim(($agent['website'] ?? '').(! empty($ref) ? ' · Ref. '.$ref : ''))])
        ->filter()->sortByDesc(fn ($line) => mb_strlen($line))->first() ?? '';
    $contactSize = TextFit::toWidth($contactLine, 68, max: 9.5, min: 6.5);
@endphp
{{-- The logo is its own absolutely positioned column: as an inline-block beside the
     text it was bumped onto a second line, growing the panel until it ran off the page. --}}
<div class="cta @empty($logo) cta-nologo @endempty">
  @if(!empty($logo))
    <div class="ctalogo"><img class="cta-logo" src="{{ $logo }}" alt=""></div>
  @endif
  <div class="ctaleft">
    <div class="name" style="font-size: {{ $nameSize }}">{{ $agent['name'] }}</div>
    <div class="role">{{ $agent['role'] }}</div>
    @if(!empty($agent['address']))
      <div class="addr">{{ $agent['address'] }}</div>
    @endif
  </div>
  <div class="contact" style="font-size: {{ $contactSize }}">
    @if(!empty($agent['phone']))
      <b>{{ $agent['phone'] }}</b><br>
    @endif
    @if(!empty($agent['email']))
      {{ $agent['email'] }}<br>
    @endif
    @if(!empty($agent['website']))
      {{ $agent['website'] }}
    @endif
    @if(!empty($ref))
      · Ref. {{ $ref }}
    @endif
  </div>
</div>
