<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\DealResource;
use App\Models\Deal;

class DealController extends CrudController
{
    protected string $model = Deal::class;
    protected string $resourceClass = DealResource::class;
    protected string $label = 'Oportunidad';
    protected array $search = ['title', 'stage'];
    protected array $with = ['lead', 'contact', 'property', 'owner'];

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
