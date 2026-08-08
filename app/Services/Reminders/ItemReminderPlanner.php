<?php

namespace App\Services\Reminders;

use App\Models\Appointment;
use App\Models\NotificationSetting;
use App\Models\TaskItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Decide cuándo toca avisar de una tarea o de una cita.
 *
 * Cada registro puede generar hasta tres avisos: el de anticipación ("empieza en
 * 30 minutos"), el de la hora exacta ("empieza ahora") y el de vencimiento, que
 * se repite una vez al día mientras siga dentro del margen configurado.
 *
 * La ventana se calcula contra la marca de tiempo del propio registro y no contra
 * el minuto en que corrió el cron: si el scheduler se atrasa, el aviso igual sale.
 */
class ItemReminderPlanner
{
    /** Margen para seguir considerando "es ahora" un inicio recién pasado. */
    private const EXACT_GRACE_MINUTES = 20;

    public function __construct(private ItemReminderCopy $copy)
    {
    }

    /** @return Reminder[] */
    public function plan(NotificationSetting $setting, string $type): array
    {
        if (! $setting->wants($type)) {
            return [];
        }

        $reminders = [];
        foreach ($this->records($setting, $type) as $record) {
            foreach ($this->stagesFor($setting, $type, $record) as [$stage, $urgency, $lead]) {
                $reminders[] = $this->copy->build($setting, $type, $record, $stage, $urgency, $lead);
            }
        }

        return $reminders;
    }

    /** @return Collection<int, Model> */
    private function records(NotificationSetting $setting, string $type): Collection
    {
        $column = $type === 'task' ? 'due_at' : 'starts_at';
        $model = $type === 'task' ? TaskItem::class : Appointment::class;
        $window = max($setting->leadMinutesFor($type), (int) $model::max('notify_lead_minutes'));

        $query = $type === 'task'
            ? TaskItem::query()->where('status', '!=', 'done')
            : Appointment::query()->whereIn('status', ['scheduled', 'confirmed'])
                ->with(['contact', 'lead', 'property']);

        return $query->where('notify_enabled', true)
            ->whereNotNull($column)
            ->whereBetween($column, [
                now()->subDays(max(1, (int) $setting->overdue_days)),
                now()->addMinutes($window),
            ])
            ->orderBy($column)->limit(200)->get();
    }

    /** @return array<int, array{0: string, 1: string, 2: int}> */
    private function stagesFor(NotificationSetting $setting, string $type, Model $record): array
    {
        $at = $record->reminderAt()?->copy();
        if (! $at) {
            return [];
        }

        $now = now();
        $lead = (int) ($record->notify_lead_minutes ?: $setting->leadMinutesFor($type));
        $grace = $at->copy()->addMinutes(self::EXACT_GRACE_MINUTES);
        $stages = [];

        if ($setting->getAttribute("{$type}_immediate_enabled")
            && $now->gte($at->copy()->subMinutes($lead)) && $now->lt($at)) {
            $stages[] = ['lead', 'soon', $lead];
        }

        if ($setting->getAttribute("{$type}_exact_enabled")
            && $now->gte($at) && $now->lte($grace)) {
            $stages[] = ['exact', 'now', $lead];
        }

        // Vencido: solo a partir de la hora configurada para el tipo, para no
        // despertar a nadie a medianoche, y una única vez por día.
        if ($setting->overdue_enabled && $now->gt($grace)
            && $now->lte($at->copy()->addDays(max(0, (int) $setting->overdue_days))->endOfDay())
            && $setting->now()->format('H:i') >= (string) $setting->getAttribute("{$type}_time")) {
            $stages[] = ['overdue:'.$setting->now()->toDateString(), 'overdue', $lead];
        }

        return $stages;
    }
}
