<?php

namespace App\Http\Controllers\Admin;

use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\NotificationSetting;
use App\Models\Property;
use App\Models\User;
use App\Support\ReminderFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentController extends CrudController
{
    protected string $model = Appointment::class;
    protected string $route = 'appointments';
    protected string $label = 'Cita';
    protected string $labelPlural = 'Agenda';
    protected string $intro = 'La **agenda** son encuentros con hora: visitas, reuniones, llamadas programadas, firmas. '
        .'Ocupan un espacio del día y avisan antes de empezar. '
        .'Lo que solo hay que hacer en algún momento, sin cita, va en **Tareas**.';
    protected array $search = ['title', 'location', 'status'];
    protected array $columns = [
        'title' => 'Cita', 'type' => 'Tipo', 'starts_at' => 'Inicio',
        'location' => 'Lugar', 'status' => 'Estado',
    ];

    protected function fields(): array
    {
        return array_merge([
            ['name' => 'title', 'label' => '¿De qué es la cita?', 'type' => 'text', 'wide' => true,
                'help' => 'Ej.: «Visita depto. San Isidro con la Sra. Sevilla».'],
            ['name' => 'type', 'label' => 'Tipo', 'type' => 'select',
                'default' => 'visit', 'options' => Appointment::TYPES],
            ['name' => 'status', 'label' => 'Estado', 'type' => 'select',
                'default' => 'scheduled', 'options' => Appointment::STATUSES],
            ['name' => 'starts_at', 'label' => 'Empieza', 'type' => 'datetime-local',
                'help' => 'Obligatorio: es la hora desde la que se calculan los avisos.'],
            ['name' => 'ends_at', 'label' => 'Termina (opcional)', 'type' => 'datetime-local',
                'help' => 'Déjalo vacío si no sabes cuánto va a durar.'],
            ['name' => 'location', 'label' => 'Lugar o enlace', 'type' => 'text',
                'help' => 'Dirección, referencia o link de la videollamada.'],
            ['name' => 'assigned_to', 'label' => 'Responsable', 'type' => 'select',
                'searchable' => true, 'options' => User::pluck('name', 'id')->all()],
            ['name' => 'contact_id', 'label' => 'Contacto', 'type' => 'select',
                'searchable' => true, 'options' => Contact::all()->pluck('full_name', 'id')->all()],
            ['name' => 'lead_id', 'label' => 'Prospecto', 'type' => 'select',
                'searchable' => true, 'options' => Lead::all()->pluck('full_name', 'id')->all()],
            ['name' => 'property_id', 'label' => 'Propiedad', 'type' => 'select',
                'searchable' => true, 'options' => Property::pluck('title', 'id')->all()],
        ], ReminderFields::forAppointment(), [
            ['name' => 'notes', 'label' => 'Notas', 'type' => 'textarea', 'wide' => true],
        ]);
    }

    protected function prepare(array $data, Request $request): array
    {
        return ReminderFields::normalize(parent::prepare($data, $request));
    }

    protected function rules(?int $id = null): array
    {
        return ReminderFields::rules() + [
            'title' => ['required', 'max:160'],
            'type' => ['required', Rule::in(array_keys(Appointment::TYPES))],
            'status' => ['required', Rule::in(array_keys(Appointment::STATUSES))],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'location' => ['nullable', 'max:200'], 'assigned_to' => ['nullable', 'exists:users,id'],
            'lead_id' => ['nullable', 'exists:leads,id'], 'contact_id' => ['nullable', 'exists:contacts,id'],
            'property_id' => ['nullable', 'exists:properties,id'], 'notes' => ['nullable', 'max:3000'],
        ];
    }

    protected function form(Model $record)
    {
        if (! $record->exists) {
            $record->notify_enabled = NotificationSetting::current()->appointment_notify_default;
        }

        return parent::form($record);
    }
}
