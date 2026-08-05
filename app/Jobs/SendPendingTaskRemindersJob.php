<?php

namespace App\Jobs;

use App\Jobs\Concerns\SendsReminderMail;
use App\Models\NotificationSetting;
use App\Models\TaskItem;
use App\Notifications\CrmReminderDigest;
use Illuminate\Bus\Queueable;

class SendPendingTaskRemindersJob
{
    use Queueable, SendsReminderMail;

    public function __construct(public int $settingId)
    {
    }

    public function handle(): bool
    {
        $setting = NotificationSetting::findOrFail($this->settingId);
        $tasks = TaskItem::query()->where('status', '!=', 'done')
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now()->addDays($setting->task_days)->endOfDay())
            ->orderBy('due_at')->limit(100)->get();
        if ($tasks->isEmpty()) return false;

        $items = $tasks->map(fn (TaskItem $task) => [
            'title' => $task->title,
            'meta' => ($task->due_at->isPast() ? 'Vencida: ' : 'Vence: ')
                .$task->due_at->format('d/m/Y H:i').' · Prioridad '.$task->priority,
            'url' => route('admin.tasks.edit', $task),
        ])->all();
        $this->sendToRecipients($setting, new CrmReminderDigest('Tareas pendientes',
            "Tareas vencidas o que vencen en {$setting->task_days} días.", $items));

        return true;
    }
}
