{{-- expects $agent: name, role, phone, email; $logo: nullable data URI --}}
<div class="foot1">
  <span>
    @if(!empty($logo))<img class="foot1-logo" src="{{ $logo }}" alt="">@endif
    <b>{{ $agent['name'] }}</b>@if(!empty($agent['role'])) · {{ $agent['role'] }}@endif
  </span>
  <span>
    @if(!empty($agent['phone']))<b>{{ $agent['phone'] }}</b>@endif
    @if(!empty($agent['email'])) · {{ $agent['email'] }}@endif
  </span>
</div>
