<?php

namespace App\Http\Controllers\Admin;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\NotificationSetting;
use App\Models\Property;
use App\Models\TaskItem;
use App\Models\User;
use App\Support\ReminderFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends CrudController
{
    protected string $model = TaskItem::class;
    protected string $route = 'tasks';
    protected string $label = 'Tarea';
    protected string $labelPlural = 'Tareas';
    protected string $intro = 'Una **tarea** es algo que tienes que hacer: llamar, enviar un contrato, preparar fotos. '
        .'Puede tener fecha límite o no tenerla. '
        .'Si en cambio es un encuentro con hora y lugar (una visita, una reunión), va en **Agenda**.';
    protected array $search = ['title', 'status', 'priority'];
    protected array $columns = [
        'title' => 'Tarea', 'priority' => 'Prioridad', 'status' => 'Estado',
        'due_at' => 'Fecha límite', 'related_type' => 'Relacionada con',
    ];

    protected function fields(): array
    {
        return array_merge([
            ['name' => 'title', 'label' => '¿Qué hay que hacer?', 'type' => 'text', 'wide' => true,
                'help' => 'Escríbelo como una acción: «Enviar tasación a la Sra. Ponce».'],
            ['name' => 'priority', 'label' => 'Prioridad', 'type' => 'select',
                'default' => 'medium', 'options' => TaskItem::PRIORITIES],
            ['name' => 'status', 'label' => 'Estado', 'type' => 'select',
                'default' => 'pending', 'options' => TaskItem::STATUSES],
            ['name' => 'due_at', 'label' => 'Fecha límite (opcional)', 'type' => 'datetime-local',
                'help' => 'Puedes dejarla vacía: la tarea queda en la lista sin generar avisos de vencimiento.'],
            ['name' => 'assigned_to', 'label' => 'Responsable', 'type' => 'select',
                'searchable' => true, 'options' => User::pluck('name', 'id')->all()],
            ['name' => 'related_type', 'label' => '¿Tiene que ver con…?', 'type' => 'select',
                'options' => ['' => 'Nada en particular'] + TaskItem::RELATED_LABELS],
            ['name' => 'related_id', 'label' => '¿Con cuál?', 'type' => 'select',
                'searchable' => true, 'depends_on' => 'related_type',
                'grouped_options' => $this->relatedOptions(),
                'help' => 'Aparece en el aviso, para saber de quién se trata sin abrir la tarea.'],
        ], ReminderFields::forTask(), [
            ['name' => 'description', 'label' => 'Detalle', 'type' => 'textarea', 'wide' => true],
        ]);
    }

    protected function prepare(array $data, Request $request): array
    {
        return ReminderFields::normalize(parent::prepare($data, $request));
    }

    protected function rules(?int $id = null): array
    {
        return ReminderFields::rules() + [
            'title' => ['required', 'max:160'], 'assigned_to' => ['nullable', 'exists:users,id'],
            'priority' => ['required', Rule::in(array_keys(TaskItem::PRIORITIES))],
            'status' => ['required', Rule::in(array_keys(TaskItem::STATUSES))],
            'due_at' => ['nullable', 'date'],
            'related_type' => ['nullable', Rule::in(array_keys(TaskItem::RELATED_MODELS))],
            'related_id' => $this->relatedIdRules(),
            'description' => ['nullable', 'max:3000'],
        ];
    }

    protected function form(Model $record)
    {
        if (! $record->exists) {
            $record->notify_enabled = NotificationSetting::current()->task_notify_default;
        }

        return parent::form($record);
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
