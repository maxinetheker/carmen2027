@php
    $stages = [
        'qualified' => ['Calificados', '#2f67a4'],
        'visit' => ['Visita', '#6f96c6'],
        'proposal' => ['Propuesta', '#e23d4f'],
        'negotiation' => ['Negociación', '#aa2538'],
    ];
@endphp
<div class="pipeline-board">
    @foreach($stages as $stage => [$label, $color])
        @php($items = $pipeline->get($stage, collect()))
        <div class="pipeline-column">
            <div class="pipeline-title"><span style="background:{{ $color }}"></span>
                <strong>{{ $label }}</strong><small>{{ $items->count() }}</small></div>
            @forelse($items as $deal)
                <a class="deal-card" href="{{ route('admin.deals.edit', $deal) }}">
                    <span>{{ $deal->title }}</span>
                    <strong>US$ {{ number_format($deal->value) }}</strong>
                    <div><small>{{ $deal->expected_close?->format('d M') ?: 'Sin fecha' }}</small>
                        <b>{{ $deal->probability }}%</b></div>
                </a>
            @empty
                <div class="pipeline-empty">Sin oportunidades</div>
            @endforelse
        </div>
    @endforeach
</div>
