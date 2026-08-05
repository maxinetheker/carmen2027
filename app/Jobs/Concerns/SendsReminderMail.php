<?php

namespace App\Jobs\Concerns;

use App\Models\DeviceToken;
use App\Models\NotificationSetting;
use App\Notifications\CrmReminderDigest;
use App\Services\FcmSender;
use Illuminate\Support\Facades\Notification;

trait SendsReminderMail
{
    protected function sendToRecipients(
        NotificationSetting $setting, CrmReminderDigest $notification
    ): void {
        foreach ($setting->recipients() as $email) {
            Notification::route('mail', $email)->notify(clone $notification);
        }

        $this->pushToDevices($notification);
    }

    private function pushToDevices(CrmReminderDigest $notification): void
    {
        $tokens = DeviceToken::query()->pluck('token')->all();
        if ($tokens === []) return;

        app(FcmSender::class)->send(
            $tokens, $notification->heading, $notification->intro
        );
    }
}
