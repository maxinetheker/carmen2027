<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\NotificationSetting;
use App\Models\TaskItem;
use App\Models\User;
use App\Notifications\CrmReminderDigest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_configure_reminders(): void
    {
        $this->seed();
        $user = User::firstOrFail();
        $this->actingAs($user)->get(route('admin.notifications.edit'))
            ->assertOk()->assertSee('Clientes por contactar')->assertSee('Agenda (citas y visitas)');

        $this->actingAs($user)->put(route('admin.notifications.update'), [
            'recipient_emails' => "avisos@example.com\nequipo@example.com",
            'timezone' => 'America/Lima', 'overdue_days' => 5,
            'follow_up_enabled' => 1, 'follow_up_frequency' => 'weekly',
            'follow_up_time' => '09:15', 'follow_up_weekday' => 2, 'follow_up_days' => 10,
            'appointment_enabled' => 1, 'appointment_frequency' => 'daily',
            'appointment_time' => '08:00', 'appointment_weekday' => 1, 'appointment_days' => 5,
            'appointment_immediate_enabled' => 1, 'appointment_lead_minutes' => 30,
            'task_enabled' => 1, 'task_frequency' => 'daily',
            'task_time' => '07:30', 'task_weekday' => 1, 'task_days' => 2,
            'task_immediate_enabled' => 1, 'task_lead_minutes' => 15,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('notification_settings', [
            'recipient_email' => 'avisos@example.com',
            'follow_up_frequency' => 'weekly', 'follow_up_days' => 10,
        ]);
        $this->assertSame(['avisos@example.com', 'equipo@example.com'],
            NotificationSetting::firstOrFail()->recipients());
    }

    public function test_command_sends_each_actionable_digest(): void
    {
        Notification::fake();
        $setting = NotificationSetting::create([
            'recipient_email' => 'avisos@example.com', 'timezone' => 'America/Lima',
        ]);
        Lead::create([
            'first_name' => 'Cliente', 'phone' => '999111222', 'status' => 'new',
            'last_contact_at' => now()->subDays(10),
        ]);
        Appointment::create([
            'title' => 'Visita', 'starts_at' => now()->addDay(), 'status' => 'scheduled',
        ]);
        TaskItem::create([
            'title' => 'Llamar', 'due_at' => now()->addHours(2), 'status' => 'pending',
        ]);

        $this->artisan('crm:send-reminders', ['--force' => true])->assertSuccessful();

        Notification::assertCount(3);
        Notification::assertSentOnDemand(CrmReminderDigest::class);
        $setting->refresh();
        $this->assertNotNull($setting->follow_up_last_sent_at);
        $this->assertNotNull($setting->appointment_last_sent_at);
        $this->assertNotNull($setting->task_last_sent_at);
    }

    public function test_admin_records_are_paginated(): void
    {
        $this->seed();
        Contact::query()->delete();
        foreach (range(1, 13) as $number) {
            Contact::create(['first_name' => "Contacto {$number}", 'phone' => "900000{$number}"]);
        }

        $this->actingAs(User::first())->get(route('admin.contacts.index'))
            ->assertOk()->assertSee('Mostrando 1–12 de 13')->assertSee('page=2', false);
        $this->actingAs(User::first())->get(route('admin.contacts.index', ['per_page' => 24]))
            ->assertOk()->assertSee('Mostrando 1–13 de 13');
    }

    public function test_paused_and_do_not_contact_people_are_excluded(): void
    {
        Notification::fake();
        NotificationSetting::create([
            'recipient_email' => 'avisos@example.com', 'timezone' => 'America/Lima',
        ]);
        Lead::create([
            'first_name' => 'Contactar', 'phone' => '900111111', 'status' => 'new',
            'follow_up_status' => 'active', 'last_contact_at' => now()->subDays(20),
        ]);
        Lead::create([
            'first_name' => 'Excluido', 'phone' => '900222222', 'status' => 'new',
            'follow_up_status' => 'do_not_contact', 'last_contact_at' => now()->subDays(20),
        ]);
        Contact::create([
            'first_name' => 'Pausado', 'phone' => '900333333',
            'follow_up_status' => 'paused', 'last_contact_at' => now()->subDays(20),
        ]);
        Contact::create([
            'first_name' => 'Programado', 'phone' => '900444444',
            'follow_up_status' => 'active', 'last_contact_at' => now()->subDays(20),
            'next_contact_at' => now()->addDays(5),
        ]);

        $this->artisan('crm:send-reminders', [
            '--force' => true, '--type' => 'follow_up',
        ])->assertSuccessful();

        Notification::assertSentOnDemand(CrmReminderDigest::class,
            fn (CrmReminderDigest $notice) => collect($notice->items)->pluck('title')->all()
                === ['Contactar · Comprador']);
        Notification::assertCount(1);
    }
}
