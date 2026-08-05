<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'recipient_emails' => $this->recipients(),
            'timezone' => $this->timezone,
        ];
        foreach (['follow_up', 'appointment', 'task'] as $key) {
            $data["{$key}_enabled"] = (bool) $this->{"{$key}_enabled"};
            $data["{$key}_frequency"] = $this->{"{$key}_frequency"};
            $data["{$key}_time"] = $this->{"{$key}_time"};
            $data["{$key}_weekday"] = $this->{"{$key}_weekday"};
            $data["{$key}_days"] = $this->{"{$key}_days"};
            $data["{$key}_last_sent_at"] = $this->{"{$key}_last_sent_at"}?->toIso8601String();
        }
        foreach (['appointment', 'task'] as $key) {
            $data["{$key}_immediate_enabled"] = (bool) $this->{"{$key}_immediate_enabled"};
            $data["{$key}_lead_minutes"] = $this->{"{$key}_lead_minutes"};
        }

        return $data;
    }
}
