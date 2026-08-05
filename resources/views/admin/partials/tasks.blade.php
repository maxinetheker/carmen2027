<div class="task-list">
    @forelse($tasks as $task)
        <a href="{{ route('admin.tasks.edit', $task) }}">
            <span class="task-check">✓</span>
            <div><strong>{{ $task->title }}</strong>
                <small>{{ $task->due_at?->isPast() ? 'Vencida' : $task->due_at?->diffForHumans() }}</small></div>
            <i class="priority-dot priority-{{ $task->priority }}"></i>
        </a>
    @empty
        <div class="empty-state compact">Todo al día.</div>
    @endforelse
</div>
