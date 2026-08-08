<?php

namespace App\Http\Controllers\Admin;

use App\Models\Contact;
use App\Support\PeopleFields;
use Illuminate\Database\Eloquent\Model;

class ContactController extends CrudController
{
    protected string $model = Contact::class;
    protected string $route = 'contacts';
    protected string $label = 'Contacto';
    protected string $labelPlural = 'Contactos';
    protected string $intro = 'Un **contacto** es tu cartera: gente con la que ya trabajas o trabajaste, con su ficha completa '
        .'(documento, dirección, cumpleaños) y su historial de llamadas. '
        .'Si la persona recién llegó y aún no la calificas, va en **Prospectos**.';
    protected array $search = ['first_name', 'last_name', 'email', 'phone', 'company'];
    protected array $columns = [
        'full_name' => 'Nombre', 'party_type' => 'Es', 'phone' => 'Teléfono',
        'email' => 'Correo', 'follow_up_status' => 'Seguimiento',
        'last_contact_at' => 'Último contacto', 'company' => 'Empresa',
    ];

    protected function fields(): array
    {
        return array_merge(
            PeopleFields::identity(),
            [
                ['name' => 'document', 'label' => 'DNI / CE / RUC', 'type' => 'text'],
                ['name' => 'company', 'label' => 'Empresa', 'type' => 'text'],
                ['name' => 'birthday', 'label' => 'Cumpleaños', 'type' => 'date'],
                ['name' => 'address', 'label' => 'Dirección', 'type' => 'text', 'wide' => true],
                ['name' => 'device_contact_name', 'label' => 'Guardado en el celular como', 'type' => 'text',
                    'help' => 'Lo completa la app al sincronizar la agenda del teléfono.'],
            ],
            PeopleFields::followUp(),
        );
    }

    protected function rules(?int $id = null): array
    {
        return PeopleFields::rules() + [
            'document' => ['nullable', 'max:30'], 'company' => ['nullable', 'max:120'],
            'birthday' => ['nullable', 'date'], 'address' => ['nullable', 'max:200'],
            'device_contact_name' => ['nullable', 'max:120'],
        ];
    }

    protected function panels(Model $record): array
    {
        return ['admin.people.contact-log' => 'Registro de contacto ('.$record->contactLogs()->count().')'];
    }
}
