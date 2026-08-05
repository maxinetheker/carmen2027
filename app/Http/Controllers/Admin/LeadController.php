<?php

namespace App\Http\Controllers\Admin;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;

class LeadController extends CrudController
{
    protected string $model = Lead::class;
    protected string $route = 'leads';
    protected string $label = 'Prospecto';
    protected string $labelPlural = 'Prospectos';
    protected array $search = ['first_name', 'last_name', 'email', 'phone'];
    protected array $columns = [
        'full_name' => 'Nombre', 'phone' => 'Teléfono', 'source' => 'Origen',
        'status' => 'Estado', 'follow_up_status' => 'Seguimiento',
        'score' => 'Puntaje', 'budget' => 'Presupuesto',
    ];

    protected function fields(): array
    {
        return [
            ['name' => 'first_name', 'label' => 'Nombre', 'type' => 'text'],
            ['name' => 'last_name', 'label' => 'Apellidos', 'type' => 'text'],
            ['name' => 'email', 'label' => 'Correo', 'type' => 'email'],
            ['name' => 'phone', 'label' => 'Teléfono', 'type' => 'tel'],
            ['name' => 'source', 'label' => 'Origen', 'type' => 'select',
                'options' => ['web' => 'Sitio web', 'referral' => 'Referido', 'social' => 'Redes', 'portal' => 'Portal inmobiliario', 'manual' => 'Registro manual']],
            ['name' => 'status', 'label' => 'Estado', 'type' => 'select',
                'options' => ['new' => 'Nuevo', 'contacted' => 'Contactado', 'qualified' => 'Calificado', 'nurturing' => 'Seguimiento', 'won' => 'Ganado', 'lost' => 'Perdido']],
            ['name' => 'score', 'label' => 'Puntaje', 'type' => 'number'],
            ['name' => 'budget', 'label' => 'Presupuesto', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'interest', 'label' => 'Interés', 'type' => 'text', 'wide' => true],
            ['name' => 'assigned_to', 'label' => 'Asignado a', 'type' => 'select',
                'searchable' => true, 'options' => User::pluck('name', 'id')->all()],
            ['name' => 'last_contact_at', 'label' => 'Último contacto', 'type' => 'datetime-local'],
            ['name' => 'follow_up_status', 'label' => 'Seguimiento', 'type' => 'select',
                'default' => 'active', 'help' => 'Pausado suspende los avisos; No contactar lo excluye hasta que lo reactives.',
                'options' => ['active' => 'Activo', 'paused' => 'Pausado', 'do_not_contact' => 'No contactar']],
            ['name' => 'next_contact_at', 'label' => 'Próximo contacto', 'type' => 'datetime-local',
                'help' => 'Si defines una fecha, no se avisará antes de ese momento.'],
            ['name' => 'notes', 'label' => 'Notas', 'type' => 'textarea', 'wide' => true],
        ];
    }

    protected function rules(?int $id = null): array
    {
        return [
            'first_name' => ['required', 'max:80'], 'last_name' => ['nullable', 'max:80'],
            'email' => ['nullable', 'email', 'max:120'], 'phone' => ['required', 'max:30'],
            'source' => ['required'], 'status' => ['required'], 'score' => ['required', 'integer', 'between:0,100'],
            'budget' => ['nullable', 'numeric', 'min:0'], 'interest' => ['nullable', 'max:160'],
            'assigned_to' => ['nullable', 'exists:users,id'], 'last_contact_at' => ['nullable', 'date'],
            'follow_up_status' => ['required', 'in:active,paused,do_not_contact'],
            'next_contact_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'max:5000'],
        ];
    }

    public function convert(int $record)
    {
        $lead = Lead::findOrFail($record);
        $contact = Contact::firstOrCreate(
            ['phone' => $lead->phone],
            $lead->only('first_name', 'last_name', 'email', 'phone', 'notes',
                'last_contact_at', 'follow_up_status', 'next_contact_at')
        );
        Deal::firstOrCreate(['lead_id' => $lead->id], [
            'contact_id' => $contact->id, 'owner_id' => $lead->assigned_to,
            'title' => 'Oportunidad · '.$lead->full_name, 'value' => $lead->budget ?? 0,
            'stage' => 'qualified', 'probability' => 25,
        ]);
        $lead->update(['status' => 'qualified']);
        $this->log($lead, 'converted', 'Prospecto convertido en contacto y oportunidad');

        return back()->with('success', 'Prospecto convertido y agregado a oportunidades.');
    }
}
