<?php

namespace Tests\Feature;

use App\Jobs\SendImmediateAppointmentReminderJob;
use App\Jobs\SendImmediateTaskReminderJob;
use App\Models\Appointment;
use App\Models\NotificationSetting;
use App\Models\ReminderDelivery;
use App\Models\TaskItem;
use App\Notifications\CrmReminderDigest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ImmediateReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_immediate_reminders_reach_all_recipients_only_once(): void
    {
        Notification::fake();
        $setting = NotificationSetting::create([
            'recipient_email' => 'uno@example.com',
            'recipient_emails' => ['uno@example.com', 'dos@example.com'],
            'timezone' => 'America/Lima',
            'appointment_lead_minutes' => 30, 'task_lead_minutes' => 30,
        ]);
        Appointment::create([
            'title' => 'Llamada importante', 'type' => 'call',
            'starts_at' => now()->addMinutes(20), 'status' => 'scheduled',
        ]);
        TaskItem::create([
            'title' => 'Enviar contrato', 'due_at' => now()->addMinutes(20),
            'status' => 'pending',
        ]);
        Appointment::create([
            'title' => 'Todavía no', 'starts_at' => now()->addHours(2),
            'status' => 'scheduled',
        ]);

        Bus::dispatchSync(new SendImmediateAppointmentReminderJob($setting->id));
        Bus::dispatchSync(new SendImmediateTaskReminderJob($setting->id));

        Notification::assertSentOnDemandTimes(CrmReminderDigest::class, 4);
        $this->assertSame(2, ReminderDelivery::count());

        Bus::dispatchSync(new SendImmediateAppointmentReminderJob($setting->id));
        Bus::dispatchSync(new SendImmediateTaskReminderJob($setting->id));
        Notification::assertSentOnDemandTimes(CrmReminderDigest::class, 4);
        $this->assertSame(2, ReminderDelivery::count());
    }
}
