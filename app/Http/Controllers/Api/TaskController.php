<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\TaskItemResource;
use App\Models\TaskItem;
use App\Support\ReminderFields;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends CrudController
{
    protected string $model = TaskItem::class;
    protected string $resourceClass = TaskItemResource::class;
    protected string $label = 'Tarea';
    protected array $search = ['title', 'status', 'priority'];
    protected array $with = ['assignedTo'];

    protected function prepare(array $data, Request $request): array
    {
        return ReminderFields::normalize($data);
    }

    protected function rules(?int $id = null): array
    {
        return ReminderFields::rules() + [
            'title' => ['required', 'max:160'], 'assigned_to' => ['nullable', 'exists:users,id'],
            'priority' => ['required'], 'status' => ['required'], 'due_at' => ['nullable', 'date'],
            'related_type' => ['nullable', Rule::in(['lead', 'contact', 'property', 'deal'])],
            'related_id' => $this->relatedIdRules(),
            'description' => ['nullable', 'max:3000'],
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
