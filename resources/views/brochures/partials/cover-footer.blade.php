{{-- expects $agent: name, role, phone, email; $logo: nullable data URI --}}
<div class="foot1 @empty($logo) foot1-nologo @endempty">
  @if(!empty($logo))
    <span class="foot1-mark"><img class="foot1-logo" src="{{ $logo }}" alt=""></span>
  @endif
  <span class="foot1-who">
    <b>{{ $agent['name'] }}</b>@if(!empty($agent['role'])) · {{ $agent['role'] }}@endif
  </span>
  <span class="foot1-contact">
    @if(!empty($agent['phone']))<b>{{ $agent['phone'] }}</b>@endif
    @if(!empty($agent['email'])) · {{ $agent['email'] }}@endif
  </span>
</div>
