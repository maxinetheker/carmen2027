{{-- expects $agent: name, role, address, phone, email, website; $logo: nullable data URI; $ref: nullable property code --}}
<div class="cta">
  <div class="ctaleft">
    @if(!empty($logo))
      <img class="cta-logo" src="{{ $logo }}" alt="">
    @endif
    <div class="cta-logo-text">
      <div class="name">{{ $agent['name'] }}</div>
      <div class="role">{{ $agent['role'] }}</div>
      @if(!empty($agent['address']))
        <div class="addr">{{ $agent['address'] }}</div>
      @endif
    </div>
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
