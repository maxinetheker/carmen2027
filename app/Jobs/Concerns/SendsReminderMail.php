<?php

namespace App\Jobs\Concerns;

use App\Models\NotificationSetting;
use App\Notifications\CrmReminderDigest;
use Illuminate\Support\Facades\Notification;

trait SendsReminderMail
{
    protected function sendToRecipients(
        NotificationSetting $setting, CrmReminderDigest $notification
    ): void {
        foreach ($setting->recipients() as $email) {
            Notification::route('mail', $email)->notify(clone $notification);
        }
    }
}
