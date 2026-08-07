{{-- expects $agent: name, role, phone, email; $logo: nullable data URI --}}
@php
    // This strip is one line by design, so the size is derived from the content instead
    // of fixed: a longer e-mail or job title shrinks the text rather than wrapping it and
    // leaving a stray word dangling underneath. Both columns share the smaller of the two
    // sizes so the left and right halves stay visually matched.
    use App\Services\Brochure\TextFit;

    // These widths MUST match .foot1-who / .foot1-contact in base-style.blade.php. When
    // they drifted apart (74mm in CSS, 80mm here) the text was sized for a column wider
    // than it actually had, and wrapped again.
    $who = trim($agent['name'].(! empty($agent['role']) ? ' · '.$agent['role'] : ''));
    $contact = trim(($agent['phone'] ?? '').(! empty($agent['email']) ? ' · '.$agent['email'] : ''), ' ·');
    $footSize = min(
        (float) TextFit::toWidth($who, empty($logo) ? 108 : 74),
        (float) TextFit::toWidth($contact, 72)
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
