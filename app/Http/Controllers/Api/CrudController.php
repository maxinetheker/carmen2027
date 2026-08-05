<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class CrudController extends Controller
{
    protected string $model;
    protected string $resourceClass;
    protected string $label;
    protected array $search = [];
    protected array $with = [];

    abstract protected function rules(?int $id = null): array;

    public function index(Request $request)
    {
        $query = ($this->model)::query();
        if ($this->with) $query->with($this->with);
        $query->latest();

        if ($term = trim((string) $request->get('q'))) {
            $query->where(function ($builder) use ($term) {
                foreach ($this->search as $index => $field) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $builder->{$method}($field, 'like', "%{$term}%");
                }
            });
        }

        $perPage = (int) $request->integer('per_page', 20);
        if ($perPage < 1 || $perPage > 100) $perPage = 20;

        return ($this->resourceClass)::collection(
            $query->paginate($perPage)->withQueryString()
        );
    }

    public function show(int $record)
    {
        return new ($this->resourceClass)($this->find($record));
    }

    public function store(Request $request)
    {
        $data = $this->prepare($request->validate($this->rules()), $request);
        $record = ($this->model)::create($data);
        $this->afterSave($record, $request);
        $this->log($record, 'created', "{$this->label} creado");

        return (new ($this->resourceClass)($this->find($record->id)))
            ->response()->setStatusCode(201);
    }

    public function update(Request $request, int $record)
    {
        $item = $this->find($record);
        $item->update($this->prepare($request->validate($this->rules($item->id)), $request));
        $this->afterSave($item, $request);
        $this->log($item, 'updated', "{$this->label} actualizado");

        return new ($this->resourceClass)($this->find($item->id));
    }

    public function destroy(int $record)
    {
        $item = $this->find($record);
        $this->log($item, 'deleted', "{$this->label} eliminado");
        $this->beforeDelete($item);
        $item->delete();

        return response()->json(['message' => "{$this->label} eliminado."]);
    }

    protected function find(int $id): Model
    {
        $query = ($this->model)::query();
        if ($this->with) $query->with($this->with);

        return $query->findOrFail($id);
    }

    protected function prepare(array $data, Request $request): array
    {
        return $data;
    }

    protected function afterSave(Model $record, Request $request): void
    {
    }

    protected function beforeDelete(Model $record): void
    {
    }

    protected function log(Model $record, string $type, string $text): void
    {
        Activity::create([
            'user_id' => auth()->id(),
            'subject_type' => $record::class,
            'subject_id' => $record->getKey(),
            'type' => $type,
            'description' => $text,
            'happened_at' => now(),
        ]);
    }
}
