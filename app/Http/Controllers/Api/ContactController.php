<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ContactResource;
use App\Models\Contact;
use App\Support\PeopleFields;

class ContactController extends CrudController
{
    protected string $model = Contact::class;
    protected string $resourceClass = ContactResource::class;
    protected string $label = 'Contacto';
    protected array $search = ['first_name', 'last_name', 'email', 'phone', 'company'];

    protected function rules(?int $id = null): array
    {
        return PeopleFields::rules() + [
            'document' => ['nullable', 'max:30'], 'company' => ['nullable', 'max:120'],
            'birthday' => ['nullable', 'date'], 'address' => ['nullable', 'max:200'],
            'device_contact_name' => ['nullable', 'max:120'],
        ];
    }
}
