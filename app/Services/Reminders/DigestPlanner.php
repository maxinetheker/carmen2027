<?php

namespace App\Services\Reminders;

use App\Models\Appointment;
use App\Models\NotificationSetting;
use App\Models\TaskItem;
use App\Support\HumanDate;

/**
 * El resumen del día: qué hay en la agenda y qué tareas quedan pendientes. Es el
 * único aviso que sale a una hora fija; todo lo demás llega cuando corresponde.
 */
class DigestPlanner
{
    public function plan(NotificationSetting $setting, string $type): ?Reminder
    {
        if (! $setting->wants($type)) {
            return null;
        }

        return $type === 'appointment'
            ? $this->appointments($setting)
            : $this->tasks($setting);
    }

    private function appointments(NotificationSetting $setting): ?Reminder
    {
        $days = max(0, (int) $setting->appointment_days);
        $appointments = Appointment::query()
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->where('notify_enabled', true)
            ->with(['contact', 'lead', 'property'])
            ->whereBetween('starts_at', [now(), now()->addDays($days)->endOfDay()])
            ->orderBy('starts_at')->limit(100)->get();
        if ($appointments->isEmpty()) {
            return null;
        }

        $items = $appointments->map(fn (Appointment $appointment) => [
            'title' => $appointment->title,
            'meta' => HumanDate::short($appointment->starts_at, $setting->timezone)
                .' · '.$appointment->type_label
                .' · '.($appointment->location ?: 'lugar por confirmar'),
            'detail' => $appointment->person_name
                ? 'Con '.$appointment->person_name : null,
            'url' => route('admin.appointments.edit', $appointment),
            'action' => 'Abrir la cita',
        ])->all();

        $next = $appointments->first();

        return new Reminder(
            type: 'appointment',
            heading: 'Tu agenda: '.$appointments->count().' '
                .($appointments->count() === 1 ? 'cita' : 'citas'),
            intro: $days === 0
                ? 'Esto es lo que tienes agendado para hoy.'
                : "Esto es lo que tienes agendado para hoy y los próximos {$days} días.",
            items: $items,
            pushTitle: '🗓️ Agenda: '.$appointments->count()
                .($appointments->count() === 1 ? ' cita' : ' citas'),
            pushBody: 'Primero: '.$next->title.' · '
                .HumanDate::short($next->starts_at, $setting->timezone),
            pushData: ['route' => 'appointments', 'url' => route('admin.appointments.index')],
            dedupeKey: sha1('digest|appointment|'.$setting->now()->toDateString()),
        );
    }

    private function tasks(NotificationSetting $setting): ?Reminder
    {
        $days = max(0, (int) $setting->task_days);
        $tasks = TaskItem::query()->where('status', '!=', 'done')
            ->where('notify_enabled', true)
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now()->addDays($days)->endOfDay())
            ->orderBy('due_at')->limit(100)->get();
        if ($tasks->isEmpty()) {
            return null;
        }

        $overdue = $tasks->filter(fn (TaskItem $task) => $task->due_at->isPast())->count();
        $items = $tasks->map(fn (TaskItem $task) => [
            'title' => $task->title,
            'meta' => ($task->due_at->isPast() ? 'Venció ' : 'Vence ')
                .HumanDate::short($task->due_at, $setting->timezone)
                .' · Prioridad '.$task->priority_label,
            'detail' => $task->related_label
                ? $task->related_type_label.': '.$task->related_label : null,
            'url' => route('admin.tasks.edit', $task),
            'action' => 'Abrir la tarea',
        ])->all();

        return new Reminder(
            type: 'task',
            heading: 'Tus tareas: '.$tasks->count().' '
                .($tasks->count() === 1 ? 'pendiente' : 'pendientes'),
            intro: $overdue
                ? "{$overdue} ya venció y el resto vence dentro de {$days} días."
                : "Tareas que vencen dentro de los próximos {$days} días.",
            items: $items,
            pushTitle: '✅ '.$tasks->count().' '
                .($tasks->count() === 1 ? 'tarea pendiente' : 'tareas pendientes'),
            pushBody: $overdue
                ? "{$overdue} vencida(s) · toca para revisarlas"
                : 'Primero: '.$tasks->first()->title,
            pushData: ['route' => 'tasks', 'url' => route('admin.tasks.index')],
            dedupeKey: sha1('digest|task|'.$setting->now()->toDateString()),
            urgency: $overdue ? 'overdue' : 'normal',
        );
    }
}
