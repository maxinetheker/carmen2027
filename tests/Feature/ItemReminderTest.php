<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\NotificationSetting;
use App\Models\ReminderDelivery;
use App\Models\TaskItem;
use App\Notifications\CrmReminderDigest;
use App\Services\CrmReminderDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ItemReminderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Marcar los resúmenes como ya enviados hoy deja a la vista solo los avisos
     * por registro, que es lo que prueba este archivo.
     */
    private function setting(array $overrides = []): NotificationSetting
    {
        return NotificationSetting::create($overrides + [
            'recipient_email' => 'uno@example.com',
            'recipient_emails' => ['uno@example.com', 'dos@example.com'],
            'timezone' => 'America/Lima',
            'appointment_lead_minutes' => 30, 'task_lead_minutes' => 30,
            'follow_up_last_sent_at' => now(),
            'appointment_last_sent_at' => now(), 'task_last_sent_at' => now(),
        ]);
    }

    private function titlesSent(): array
    {
        $titles = [];
        Notification::assertSentOnDemand(CrmReminderDigest::class,
            function (CrmReminderDigest $notice) use (&$titles) {
                $titles[] = $notice->items[0]['title'];

                return true;
            });

        return array_values(array_unique($titles));
    }

    public function test_each_record_gets_its_own_reminder_only_once(): void
    {
        Notification::fake();
        $this->setting();
        Appointment::create(['title' => 'Visita San Isidro', 'type' => 'visit',
            'starts_at' => now()->addMinutes(20), 'status' => 'scheduled']);
        TaskItem::create(['title' => 'Enviar contrato',
            'due_at' => now()->addMinutes(20), 'status' => 'pending']);
        Appointment::create(['title' => 'Todavía no',
            'starts_at' => now()->addHours(4), 'status' => 'scheduled']);

        app(CrmReminderDispatcher::class)->run();

        // Dos avisos (una cita, una tarea) × dos destinatarios.
        Notification::assertSentOnDemandTimes(CrmReminderDigest::class, 4);
        $this->assertSame(2, ReminderDelivery::count());

        app(CrmReminderDispatcher::class)->run();
        Notification::assertSentOnDemandTimes(CrmReminderDigest::class, 4);
    }

    public function test_reminder_names_the_record_instead_of_a_generic_heading(): void
    {
        Notification::fake();
        $this->setting();
        TaskItem::create(['title' => 'Llamar a la Sra. Ponce',
            'due_at' => now()->addMinutes(10), 'status' => 'pending', 'priority' => 'high']);

        app(CrmReminderDispatcher::class)->run(type: 'task');

        Notification::assertSentOnDemand(CrmReminderDigest::class,
            fn (CrmReminderDigest $notice) => $notice->items[0]['title'] === 'Llamar a la Sra. Ponce'
                && str_contains($notice->intro, 'Llamar a la Sra. Ponce')
                && str_contains($notice->items[0]['meta'], 'Prioridad Alta'));
    }

    public function test_overdue_tasks_keep_warning_within_the_configured_window(): void
    {
        Notification::fake();
        $this->setting(['task_time' => '00:00', 'overdue_days' => 3]);
        TaskItem::create(['title' => 'Vencida', 'due_at' => now()->subDays(2), 'status' => 'pending']);
        TaskItem::create(['title' => 'Demasiado vieja', 'due_at' => now()->subDays(9), 'status' => 'pending']);

        app(CrmReminderDispatcher::class)->run(type: 'task');

        $this->assertSame(['Vencida'], $this->titlesSent());
        Notification::assertSentOnDemand(CrmReminderDigest::class,
            fn (CrmReminderDigest $notice) => $notice->urgency === 'overdue');
    }

    public function test_records_with_notifications_off_are_skipped(): void
    {
        Notification::fake();
        $this->setting();
        TaskItem::create(['title' => 'Sin aviso', 'due_at' => now()->addMinutes(10),
            'status' => 'pending', 'notify_enabled' => false]);

        app(CrmReminderDispatcher::class)->run(type: 'task');

        Notification::assertNothingSent();
    }

    public function test_per_record_lead_time_overrides_the_general_one(): void
    {
        Notification::fake();
        $this->setting(['task_lead_minutes' => 5]);
        TaskItem::create(['title' => 'Con anticipación propia', 'status' => 'pending',
            'due_at' => now()->addMinutes(90), 'notify_lead_minutes' => 120]);

        app(CrmReminderDispatcher::class)->run(type: 'task');

        $this->assertSame(['Con anticipación propia'], $this->titlesSent());
    }

    public function test_disabled_channels_stop_the_reminder(): void
    {
        Notification::fake();
        $this->setting(['task_email_enabled' => false, 'task_push_enabled' => false]);
        TaskItem::create(['title' => 'Silenciada', 'due_at' => now()->addMinutes(10),
            'status' => 'pending']);

        app(CrmReminderDispatcher::class)->run(type: 'task');

        Notification::assertNothingSent();
        $this->assertSame(0, ReminderDelivery::count());
    }
}
