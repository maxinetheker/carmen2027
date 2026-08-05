<?php

namespace App\Http\Controllers\Admin;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Property;
use App\Models\User;

class DealController extends CrudController
{
    protected string $model = Deal::class;
    protected string $route = 'deals';
    protected string $label = 'Oportunidad';
    protected string $labelPlural = 'Oportunidades de venta';
    protected array $search = ['title', 'stage'];
    protected array $columns = [
        'title' => 'Oportunidad', 'stage' => 'Etapa', 'value' => 'Valor',
        'probability' => 'Probabilidad', 'expected_close' => 'Cierre esperado',
    ];

    protected function fields(): array
    {
        return [
            ['name' => 'title', 'label' => 'Título', 'type' => 'text', 'wide' => true],
            ['name' => 'lead_id', 'label' => 'Prospecto', 'type' => 'select',
                'searchable' => true, 'options' => Lead::all()->pluck('full_name', 'id')->all()],
            ['name' => 'contact_id', 'label' => 'Contacto', 'type' => 'select',
                'searchable' => true, 'options' => Contact::all()->pluck('full_name', 'id')->all()],
            ['name' => 'property_id', 'label' => 'Propiedad', 'type' => 'select',
                'searchable' => true, 'options' => Property::pluck('title', 'id')->all()],
            ['name' => 'owner_id', 'label' => 'Responsable', 'type' => 'select',
                'searchable' => true, 'options' => User::pluck('name', 'id')->all()],
            ['name' => 'stage', 'label' => 'Etapa', 'type' => 'select',
                'options' => ['qualified' => 'Calificado', 'visit' => 'Visita', 'proposal' => 'Propuesta', 'negotiation' => 'Negociación', 'won' => 'Ganada', 'lost' => 'Perdida']],
            ['name' => 'value', 'label' => 'Valor', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'currency', 'label' => 'Moneda', 'type' => 'select',
                'options' => ['USD' => 'USD', 'PEN' => 'PEN']],
            ['name' => 'probability', 'label' => 'Probabilidad %', 'type' => 'number'],
            ['name' => 'expected_close', 'label' => 'Cierre esperado', 'type' => 'date'],
            ['name' => 'notes', 'label' => 'Notas', 'type' => 'textarea', 'wide' => true],
        ];
    }

    protected function rules(?int $id = null): array
    {
        return [
            'title' => ['required', 'max:160'], 'lead_id' => ['nullable', 'exists:leads,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'], 'property_id' => ['nullable', 'exists:properties,id'],
            'owner_id' => ['nullable', 'exists:users,id'], 'stage' => ['required'],
            'value' => ['required', 'numeric', 'min:0'], 'currency' => ['required'],
            'probability' => ['required', 'integer', 'between:0,100'],
            'expected_close' => ['nullable', 'date'], 'notes' => ['nullable', 'max:5000'],
        ];
    }
}
