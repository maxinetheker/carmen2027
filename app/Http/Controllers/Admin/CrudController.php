<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class CrudController extends Controller
{
    protected string $model;
    protected string $route;
    protected string $label;
    protected string $labelPlural;
    protected array $columns = [];
    protected array $search = [];

    abstract protected function fields(): array;
    abstract protected function rules(?int $id = null): array;

    public function index(Request $request)
    {
        $query = ($this->model)::query()->latest();
        $perPage = (int) $request->integer('per_page', 12);
        if (! in_array($perPage, [12, 24, 48], true)) $perPage = 12;
        if ($term = trim((string) $request->get('q'))) {
            $query->where(function ($builder) use ($term) {
                foreach ($this->search as $index => $field) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $builder->{$method}($field, 'like', "%{$term}%");
                }
            });
        }

        return view('admin.resources.index', [
            'records' => $query->paginate($perPage)->withQueryString(),
            'columns' => $this->columns,
            'route' => $this->route,
            'label' => $this->label,
            'labelPlural' => $this->labelPlural,
        ]);
    }

    public function create()
    {
        return $this->form(new $this->model);
    }

    public function store(Request $request)
    {
        $data = $this->prepare($request->validate($this->rules()), $request);
        $record = ($this->model)::create($data);
        $this->afterSave($record, $request);
        $this->log($record, 'created', "{$this->label} creado");

        return redirect()->route("admin.{$this->route}.index")
            ->with('success', "{$this->label} creado correctamente.");
    }

    public function edit(int $record)
    {
        return $this->form(($this->model)::findOrFail($record));
    }

    public function update(Request $request, int $record)
    {
        $item = ($this->model)::findOrFail($record);
        $item->update($this->prepare(
            $request->validate($this->rules($record)), $request
        ));
        $this->afterSave($item, $request);
        $this->log($item, 'updated', "{$this->label} actualizado");

        return redirect()->route("admin.{$this->route}.index")
            ->with('success', "{$this->label} actualizado.");
    }

    public function destroy(int $record)
    {
        $item = ($this->model)::findOrFail($record);
        $this->log($item, 'deleted', "{$this->label} eliminado");
        $this->beforeDelete($item);
        $item->delete();

        return back()->with('success', "{$this->label} eliminado.");
    }

    protected function form(Model $record)
    {
        return view('admin.resources.form', [
            'record' => $record,
            'fields' => $this->fields(),
            'route' => $this->route,
            'label' => $this->label,
        ]);
    }

    protected function prepare(array $data, Request $request): array
    {
        foreach ($this->fields() as $field) {
            if (($field['type'] ?? '') === 'checkbox') {
                $data[$field['name']] = $request->boolean($field['name']);
            }
        }
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
