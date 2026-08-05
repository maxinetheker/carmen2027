<?php

namespace App\Http\Controllers\Admin;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Property;
use App\Models\TaskItem;
use App\Models\User;
use Illuminate\Validation\Rule;

class TaskController extends CrudController
{
    protected string $model = TaskItem::class;
    protected string $route = 'tasks';
    protected string $label = 'Tarea';
    protected string $labelPlural = 'Tareas';
    protected array $search = ['title', 'status', 'priority'];
    protected array $columns = [
        'title' => 'Tarea', 'priority' => 'Prioridad',
        'status' => 'Estado', 'due_at' => 'Vence', 'related_type' => 'Relacionado con',
    ];

    protected function fields(): array
    {
        return [
            ['name' => 'title', 'label' => 'Título', 'type' => 'text', 'wide' => true],
            ['name' => 'assigned_to', 'label' => 'Asignado a', 'type' => 'select',
                'searchable' => true, 'options' => User::pluck('name', 'id')->all()],
            ['name' => 'priority', 'label' => 'Prioridad', 'type' => 'select',
                'options' => ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'urgent' => 'Urgente']],
            ['name' => 'status', 'label' => 'Estado', 'type' => 'select',
                'options' => ['pending' => 'Pendiente', 'doing' => 'En curso', 'done' => 'Completada']],
            ['name' => 'due_at', 'label' => 'Fecha límite', 'type' => 'datetime-local'],
            ['name' => 'related_type', 'label' => 'Tipo relacionado', 'type' => 'select',
                'options' => ['' => 'Sin relación', 'lead' => 'Prospecto', 'contact' => 'Contacto', 'property' => 'Propiedad', 'deal' => 'Oportunidad']],
            ['name' => 'related_id', 'label' => 'Registro relacionado', 'type' => 'select',
                'searchable' => true, 'depends_on' => 'related_type',
                'grouped_options' => $this->relatedOptions()],
            ['name' => 'description', 'label' => 'Descripción', 'type' => 'textarea', 'wide' => true],
        ];
    }

    protected function rules(?int $id = null): array
    {
        return [
            'title' => ['required', 'max:160'], 'assigned_to' => ['nullable', 'exists:users,id'],
            'priority' => ['required'], 'status' => ['required'], 'due_at' => ['nullable', 'date'],
            'related_type' => ['nullable', Rule::in(['lead', 'contact', 'property', 'deal'])],
            'related_id' => $this->relatedIdRules(),
            'description' => ['nullable', 'max:3000'],
        ];
    }

    private function relatedOptions(): array
    {
        return [
            'lead' => Lead::orderBy('first_name')->get()->mapWithKeys(
                fn (Lead $lead) => [$lead->id => $lead->full_name]
            )->all(),
            'contact' => Contact::orderBy('first_name')->get()->mapWithKeys(
                fn (Contact $contact) => [$contact->id => $contact->full_name]
            )->all(),
            'property' => Property::orderBy('title')->pluck('title', 'id')->all(),
            'deal' => Deal::orderBy('title')->pluck('title', 'id')->all(),
        ];
    }

    private function relatedIdRules(): array
    {
        $table = match (request('related_type')) {
            'lead' => 'leads', 'contact' => 'contacts',
            'property' => 'properties', 'deal' => 'deals',
            default => null,
        };

        return $table
            ? ['required', 'integer', 'min:1', Rule::exists($table, 'id')]
            : ['nullable', 'prohibited'];
    }
}
