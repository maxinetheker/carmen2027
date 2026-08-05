<?php

namespace App\Services;

use App\Jobs\SendAppointmentRemindersJob;
use App\Jobs\SendFollowUpRemindersJob;
use App\Jobs\SendImmediateAppointmentReminderJob;
use App\Jobs\SendImmediateTaskReminderJob;
use App\Jobs\SendPendingTaskRemindersJob;
use App\Models\NotificationSetting;
use Illuminate\Support\Facades\Bus;

class CrmReminderDispatcher
{
    private array $jobs = [
        'follow_up' => SendFollowUpRemindersJob::class,
        'appointment' => SendAppointmentRemindersJob::class,
        'task' => SendPendingTaskRemindersJob::class,
    ];
    private array $immediateJobs = [
        'appointment' => SendImmediateAppointmentReminderJob::class,
        'task' => SendImmediateTaskReminderJob::class,
    ];

    public function run(bool $force = false, string $type = 'all'): array
    {
        $setting = NotificationSetting::current();
        $sent = [];
        foreach ($this->jobs as $key => $job) {
            if ($type !== 'all' && $type !== $key) continue;
            if (! $setting->getAttribute("{$key}_enabled")) continue;
            if (! $force && ! $this->isDue($setting, $key)) continue;
            $delivered = Bus::dispatchSync(new $job($setting->id));
            $setting->update(["{$key}_last_sent_at" => now()]);
            if ($delivered) $sent[] = $key;
        }
        foreach ($this->immediateJobs as $key => $job) {
            if ($type !== 'all' && $type !== $key) continue;
            if (! $setting->getAttribute("{$key}_enabled")
                || ! $setting->getAttribute("{$key}_immediate_enabled")) continue;
            if (Bus::dispatchSync(new $job($setting->id))) $sent[] = "{$key}_immediate";
        }

        return $sent;
    }

    private function isDue(NotificationSetting $setting, string $key): bool
    {
        $now = now($setting->timezone);
        if ($now->format('H:i') < $setting->getAttribute("{$key}_time")) return false;
        $last = $setting->getAttribute("{$key}_last_sent_at")?->timezone($setting->timezone);
        if ($setting->getAttribute("{$key}_frequency") === 'daily') {
            return ! $last || ! $last->isSameDay($now);
        }

        $weekday = (int) $setting->getAttribute("{$key}_weekday");
        return $now->dayOfWeekIso === $weekday
            && (! $last || ! $last->isSameWeek($now));
    }
}
