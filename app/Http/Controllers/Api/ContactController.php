<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ContactResource;
use App\Models\Contact;

class ContactController extends CrudController
{
    protected string $model = Contact::class;
    protected string $resourceClass = ContactResource::class;
    protected string $label = 'Contacto';
    protected array $search = ['first_name', 'last_name', 'email', 'phone'];

    protected function rules(?int $id = null): array
    {
        return [
            'first_name' => ['required', 'max:80'], 'last_name' => ['nullable', 'max:80'],
            'email' => ['nullable', 'email', 'max:120'], 'phone' => ['required', 'max:30'],
            'document' => ['nullable', 'max:30'], 'company' => ['nullable', 'max:120'],
            'birthday' => ['nullable', 'date'], 'last_contact_at' => ['nullable', 'date'],
            'follow_up_status' => ['required', 'in:active,paused,do_not_contact'],
            'next_contact_at' => ['nullable', 'date'],
            'address' => ['nullable', 'max:200'],
            'notes' => ['nullable', 'max:5000'],
        ];
    }
}
