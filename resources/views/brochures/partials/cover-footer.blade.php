{{-- expects $agent: name, role, phone, email; $logo: nullable data URI --}}
@php
    // This strip is one line by design, so the size is derived from the content instead
    // of fixed: a longer e-mail or job title shrinks the text rather than wrapping it and
    // leaving a stray word dangling underneath. Both columns share the smaller of the two
    // sizes so the left and right halves stay visually matched.
    use App\Services\Brochure\TextFit;

    $who = trim($agent['name'].(! empty($agent['role']) ? ' · '.$agent['role'] : ''));
    $contact = trim(($agent['phone'] ?? '').(! empty($agent['email']) ? ' · '.$agent['email'] : ''), ' ·');
    $column = empty($logo) ? 100 : 80;
    $footSize = min(
        (float) TextFit::toWidth($who, $column),
        (float) TextFit::toWidth($contact, 80)
    ).'pt';
@endphp
<div class="foot1 @empty($logo) foot1-nologo @endempty" style="font-size: {{ $footSize }}">
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
