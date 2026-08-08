<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Support\ReminderFields;
use Illuminate\Http\Request;

class AppointmentController extends CrudController
{
    protected string $model = Appointment::class;
    protected string $resourceClass = AppointmentResource::class;
    protected string $label = 'Cita';
    protected array $search = ['title', 'location', 'status'];
    protected array $with = ['assignedTo', 'lead', 'contact', 'property'];

    protected function prepare(array $data, Request $request): array
    {
        return ReminderFields::normalize($data);
    }

    protected function rules(?int $id = null): array
    {
        return ReminderFields::rules() + [
            'title' => ['required', 'max:160'], 'type' => ['required'], 'status' => ['required'],
            'starts_at' => ['required', 'date'], 'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'location' => ['nullable', 'max:200'], 'assigned_to' => ['nullable', 'exists:users,id'],
            'lead_id' => ['nullable', 'exists:leads,id'], 'contact_id' => ['nullable', 'exists:contacts,id'],
            'property_id' => ['nullable', 'exists:properties,id'], 'notes' => ['nullable', 'max:3000'],
        ];
    }
}
