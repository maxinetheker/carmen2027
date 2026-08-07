{{-- expects $agent: name, role, address, phone, email, website; $logo: nullable data URI; $ref: nullable property code --}}
{{-- The logo is its own absolutely positioned column: as an inline-block beside the
     text it was bumped onto a second line, growing the panel until it ran off the page. --}}
<div class="cta @empty($logo) cta-nologo @endempty">
  @if(!empty($logo))
    <div class="ctalogo"><img class="cta-logo" src="{{ $logo }}" alt=""></div>
  @endif
  <div class="ctaleft">
    <div class="name">{{ $agent['name'] }}</div>
    <div class="role">{{ $agent['role'] }}</div>
    @if(!empty($agent['address']))
      <div class="addr">{{ $agent['address'] }}</div>
    @endif
  </div>
  <div class="contact">
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
