<div class="activity-list">
    @forelse($activities as $activity)
        <article>
            <span class="activity-dot"></span>
            <div><p>{{ $activity->description }}</p>
                <small>{{ $activity->user?->name ?? 'Sitio web' }} · {{ $activity->happened_at->diffForHumans() }}</small></div>
        </article>
    @empty
        <div class="empty-state compact">La actividad aparecerá aquí.</div>
    @endforelse
</div>
