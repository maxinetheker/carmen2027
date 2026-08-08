<?php

namespace App\Http\Resources;

use App\Models\NotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'recipient_emails' => $this->recipients(),
            'timezone' => $this->timezone,
            'last_run_at' => $this->last_run_at?->toIso8601String(),
            'overdue_enabled' => (bool) $this->overdue_enabled,
            'overdue_days' => (int) $this->overdue_days,
            'task_notify_default' => (bool) $this->task_notify_default,
            'appointment_notify_default' => (bool) $this->appointment_notify_default,
        ];
        foreach (NotificationSetting::TYPES as $key) {
            $data["{$key}_enabled"] = (bool) $this->{"{$key}_enabled"};
            $data["{$key}_email_enabled"] = (bool) $this->{"{$key}_email_enabled"};
            $data["{$key}_push_enabled"] = (bool) $this->{"{$key}_push_enabled"};
            $data["{$key}_frequency"] = $this->{"{$key}_frequency"};
            $data["{$key}_time"] = $this->{"{$key}_time"};
            $data["{$key}_weekday"] = $this->{"{$key}_weekday"};
            $data["{$key}_days"] = $this->{"{$key}_days"};
            $data["{$key}_last_sent_at"] = $this->{"{$key}_last_sent_at"}?->toIso8601String();
        }
        foreach (['appointment', 'task'] as $key) {
            $data["{$key}_immediate_enabled"] = (bool) $this->{"{$key}_immediate_enabled"};
            $data["{$key}_exact_enabled"] = (bool) $this->{"{$key}_exact_enabled"};
            $data["{$key}_lead_minutes"] = (int) $this->{"{$key}_lead_minutes"};
        }

        return $data;
    }
}
