<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Property;
use App\Models\TaskItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class CrmDemoSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::first();
        $leadRows = [
            ['Lucía', 'Paredes', '999 241 810', 'portal', 'qualified', 92, 420000],
            ['Andrés', 'Salazar', '988 172 144', 'referral', 'contacted', 78, 300000],
            ['Mariana', 'Costa', '977 445 230', 'web', 'new', 64, 250000],
            ['Diego', 'Rivas', '966 188 440', 'social', 'nurturing', 56, 180000],
            ['Paola', 'Núñez', '955 607 122', 'web', 'won', 96, 485000],
            ['Jorge', 'Tamayo', '944 702 510', 'portal', 'lost', 35, 210000],
        ];
        foreach ($leadRows as $row) {
            [$first, $last, $phone, $source, $status, $score, $budget] = $row;
            Lead::updateOrCreate(['phone' => $phone], [
                'first_name' => $first, 'last_name' => $last,
                'email' => strtolower($first).'.'.strtolower($last).'@example.com',
                'source' => $source, 'status' => $status, 'score' => $score,
                'budget' => $budget, 'interest' => 'Departamento en Lima Moderna',
                'assigned_to' => $owner->id, 'last_contact_at' => now()->subDays(random_int(0, 8)),
            ]);
        }

        foreach (Lead::take(3)->get() as $lead) {
            Contact::firstOrCreate(['phone' => $lead->phone], [
                'first_name' => $lead->first_name, 'last_name' => $lead->last_name,
                'email' => $lead->email, 'notes' => 'Contacto originado en el flujo comercial.',
            ]);
        }

        $stages = ['qualified', 'visit', 'proposal', 'negotiation', 'won'];
        foreach (Lead::take(5)->get() as $index => $lead) {
            Deal::updateOrCreate(['lead_id' => $lead->id], [
                'contact_id' => Contact::where('phone', $lead->phone)->value('id'),
                'property_id' => Property::skip($index)->value('id'),
                'owner_id' => $owner->id, 'title' => 'Operación · '.$lead->full_name,
                'stage' => $stages[$index], 'value' => $lead->budget,
                'currency' => 'USD', 'probability' => [25, 45, 65, 80, 100][$index],
                'expected_close' => now()->addDays(10 + ($index * 8))->toDateString(),
            ]);
        }

        $tasks = [
            ['Preparar comparativo de mercado', 'high', 1],
            ['Confirmar visita en Miraflores', 'urgent', 0],
            ['Enviar minuta al propietario', 'medium', 3],
            ['Actualizar fotografías del inventario', 'low', 6],
        ];
        foreach ($tasks as [$title, $priority, $days]) {
            TaskItem::firstOrCreate(['title' => $title], [
                'assigned_to' => $owner->id, 'priority' => $priority,
                'status' => 'pending', 'due_at' => now()->addDays($days),
            ]);
        }

        Appointment::firstOrCreate(['title' => 'Visita · Ático en Miraflores'], [
            'assigned_to' => $owner->id, 'lead_id' => Lead::first()->id,
            'property_id' => Property::first()->id, 'type' => 'visit',
            'starts_at' => now()->addDay()->setTime(10, 30),
            'ends_at' => now()->addDay()->setTime(11, 30),
            'location' => 'Malecón de Miraflores', 'status' => 'confirmed',
        ]);
    }
}
