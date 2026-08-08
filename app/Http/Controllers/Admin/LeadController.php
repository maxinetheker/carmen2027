<?php

namespace App\Http\Controllers\Admin;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use App\Support\PeopleFields;
use Illuminate\Database\Eloquent\Model;

class LeadController extends CrudController
{
    protected string $model = Lead::class;
    protected string $route = 'leads';
    protected string $label = 'Prospecto';
    protected string $labelPlural = 'Prospectos';
    protected string $intro = 'Un **prospecto** es alguien que recién llegó y todavía no sabes si va a comprar o vender contigo: '
        .'aquí lo calificas (origen, presupuesto, interés, puntaje) y lo mueves por el embudo. '
        .'Cuando ya es cliente tuyo, usa **Convertir** y pasa a Contactos con su ficha completa.';
    protected array $search = ['first_name', 'last_name', 'email', 'phone'];
    protected array $columns = [
        'full_name' => 'Nombre', 'party_type' => 'Busca', 'phone' => 'Teléfono',
        'source' => 'Origen', 'status' => 'Etapa del embudo',
        'follow_up_status' => 'Seguimiento', 'last_contact_at' => 'Último contacto',
    ];

    protected function fields(): array
    {
        return array_merge(
            PeopleFields::identity(),
            [
                ['name' => 'source', 'label' => 'Cómo llegó', 'type' => 'select',
                    'options' => ['web' => 'Sitio web', 'referral' => 'Referido', 'social' => 'Redes', 'portal' => 'Portal inmobiliario', 'manual' => 'Registro manual']],
                ['name' => 'status', 'label' => 'Etapa del embudo', 'type' => 'select',
                    'help' => 'Nuevo → Contactado → Calificado. Al calificarlo conviene convertirlo en contacto.',
                    'options' => ['new' => 'Nuevo', 'contacted' => 'Contactado', 'qualified' => 'Calificado', 'nurturing' => 'En seguimiento', 'won' => 'Ganado', 'lost' => 'Perdido']],
                ['name' => 'score', 'label' => 'Puntaje (0-100)', 'type' => 'number',
                    'help' => 'Qué tan cerca está de concretar. Ordena a quién llamar primero.'],
                ['name' => 'budget', 'label' => 'Presupuesto', 'type' => 'number', 'step' => '0.01'],
                ['name' => 'assigned_to', 'label' => 'Asignado a', 'type' => 'select',
                    'searchable' => true, 'options' => User::pluck('name', 'id')->all()],
            ],
            PeopleFields::followUp(),
        );
    }

    protected function rules(?int $id = null): array
    {
        return PeopleFields::rules() + [
            'source' => ['required'], 'status' => ['required'],
            'score' => ['required', 'integer', 'between:0,100'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ];
    }

    protected function panels(Model $record): array
    {
        return ['admin.people.contact-log' => 'Registro de contacto ('.$record->contactLogs()->count().')'];
    }

    public function convert(int $record)
    {
        $lead = Lead::findOrFail($record);
        $contact = Contact::firstOrCreate(
            ['phone' => $lead->phone],
            $lead->only('first_name', 'last_name', 'email', 'phone', 'notes', 'party_type',
                'last_contact_at', 'follow_up_status', 'next_contact_at',
                'notify_email', 'notify_push', 'device_contact_name')
        );
        // La bitácora se muda con la persona: si no, al convertir un prospecto se
        // pierde el historial de llamadas que justamente motivó convertirlo.
        $lead->contactLogs()->update([
            'subject_type' => Contact::class, 'subject_id' => $contact->id,
        ]);
        Deal::firstOrCreate(['lead_id' => $lead->id], [
            'contact_id' => $contact->id, 'owner_id' => $lead->assigned_to,
            'title' => 'Oportunidad · '.$lead->full_name, 'value' => $lead->budget ?? 0,
            'stage' => 'qualified', 'probability' => 25,
        ]);
        $lead->update(['status' => 'qualified']);
        $this->log($lead, 'converted', 'Prospecto convertido en contacto y oportunidad');

        return back()->with('success', 'Prospecto convertido: ya está en Contactos con su historial y en Oportunidades.');
    }
}
