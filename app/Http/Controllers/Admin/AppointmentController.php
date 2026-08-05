<?php

namespace App\Http\Controllers\Admin;

use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Property;
use App\Models\User;

class AppointmentController extends CrudController
{
    protected string $model = Appointment::class;
    protected string $route = 'appointments';
    protected string $label = 'Cita';
    protected string $labelPlural = 'Agenda';
    protected array $search = ['title', 'location', 'status'];
    protected array $columns = [
        'title' => 'Cita', 'type' => 'Tipo', 'starts_at' => 'Inicio',
        'location' => 'Lugar', 'status' => 'Estado',
    ];

    protected function fields(): array
    {
        return [
            ['name' => 'title', 'label' => 'Título', 'type' => 'text', 'wide' => true],
            ['name' => 'type', 'label' => 'Tipo', 'type' => 'select',
                'options' => ['visit' => 'Visita', 'call' => 'Llamada', 'meeting' => 'Reunión', 'signing' => 'Firma']],
            ['name' => 'status', 'label' => 'Estado', 'type' => 'select',
                'options' => ['scheduled' => 'Programada', 'confirmed' => 'Confirmada', 'done' => 'Realizada', 'cancelled' => 'Cancelada']],
            ['name' => 'starts_at', 'label' => 'Inicio', 'type' => 'datetime-local'],
            ['name' => 'ends_at', 'label' => 'Fin', 'type' => 'datetime-local'],
            ['name' => 'location', 'label' => 'Lugar / enlace', 'type' => 'text'],
            ['name' => 'assigned_to', 'label' => 'Responsable', 'type' => 'select',
                'searchable' => true, 'options' => User::pluck('name', 'id')->all()],
            ['name' => 'lead_id', 'label' => 'Prospecto', 'type' => 'select',
                'searchable' => true, 'options' => Lead::all()->pluck('full_name', 'id')->all()],
            ['name' => 'contact_id', 'label' => 'Contacto', 'type' => 'select',
                'searchable' => true, 'options' => Contact::all()->pluck('full_name', 'id')->all()],
            ['name' => 'property_id', 'label' => 'Propiedad', 'type' => 'select',
                'searchable' => true, 'options' => Property::pluck('title', 'id')->all()],
            ['name' => 'notes', 'label' => 'Notas', 'type' => 'textarea', 'wide' => true],
        ];
    }

    protected function rules(?int $id = null): array
    {
        return [
            'title' => ['required', 'max:160'], 'type' => ['required'], 'status' => ['required'],
            'starts_at' => ['required', 'date'], 'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'location' => ['nullable', 'max:200'], 'assigned_to' => ['nullable', 'exists:users,id'],
            'lead_id' => ['nullable', 'exists:leads,id'], 'contact_id' => ['nullable', 'exists:contacts,id'],
            'property_id' => ['nullable', 'exists:properties,id'], 'notes' => ['nullable', 'max:3000'],
        ];
    }
}
