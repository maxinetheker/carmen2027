<?php

namespace App\Jobs;

use App\Jobs\Concerns\SendsReminderMail;
use App\Models\NotificationSetting;
use App\Models\ReminderDelivery;
use App\Models\TaskItem;
use App\Notifications\CrmReminderDigest;
use Illuminate\Bus\Queueable;

class SendImmediateTaskReminderJob
{
    use Queueable, SendsReminderMail;

    public function __construct(public int $settingId)
    {
    }

    public function handle(): bool
    {
        $setting = NotificationSetting::findOrFail($this->settingId);
        $now = now();
        $tasks = TaskItem::query()->where('status', '!=', 'done')
            ->whereBetween('due_at', [$now, $now->copy()->addMinutes($setting->task_lead_minutes)])
            ->orderBy('due_at')->limit(50)->get()
            ->reject(fn ($item) => ReminderDelivery::where(
                'reminder_key', $this->key($item)
            )->exists())->values();
        if ($tasks->isEmpty()) return false;

        $items = $tasks->map(fn (TaskItem $item) => [
            'title' => $item->title,
            'meta' => 'Vence: '.$item->due_at->format('d/m/Y H:i')
                .' · Prioridad '.$item->priority,
            'url' => route('admin.tasks.edit', $item),
        ])->all();
        $this->sendToRecipients($setting, new CrmReminderDigest(
            'Recordatorio inmediato de tarea',
            "Estas tareas vencen en los próximos {$setting->task_lead_minutes} minutos.", $items
        ));
        foreach ($tasks as $item) $this->remember($item);

        return true;
    }

    private function key(TaskItem $item): string
    {
        return sha1("task|{$item->id}|{$item->due_at->timestamp}");
    }

    private function remember(TaskItem $item): void
    {
        ReminderDelivery::firstOrCreate(['reminder_key' => $this->key($item)], [
            'type' => 'task', 'reminderable_type' => TaskItem::class,
            'reminderable_id' => $item->id, 'scheduled_for' => $item->due_at,
            'sent_at' => now(),
        ]);
    }
}
