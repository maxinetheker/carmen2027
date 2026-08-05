<?php

namespace App\Http\Controllers\Admin;

use App\Models\Contact;

class ContactController extends CrudController
{
    protected string $model = Contact::class;
    protected string $route = 'contacts';
    protected string $label = 'Contacto';
    protected string $labelPlural = 'Contactos';
    protected array $search = ['first_name', 'last_name', 'email', 'phone'];
    protected array $columns = [
        'full_name' => 'Nombre', 'phone' => 'Teléfono',
        'email' => 'Correo', 'follow_up_status' => 'Seguimiento',
        'company' => 'Empresa', 'document' => 'Documento',
    ];

    protected function fields(): array
    {
        return [
            ['name' => 'first_name', 'label' => 'Nombre', 'type' => 'text'],
            ['name' => 'last_name', 'label' => 'Apellidos', 'type' => 'text'],
            ['name' => 'email', 'label' => 'Correo', 'type' => 'email'],
            ['name' => 'phone', 'label' => 'Teléfono', 'type' => 'tel'],
            ['name' => 'document', 'label' => 'DNI / CE', 'type' => 'text'],
            ['name' => 'company', 'label' => 'Empresa', 'type' => 'text'],
            ['name' => 'birthday', 'label' => 'Cumpleaños', 'type' => 'date'],
            ['name' => 'last_contact_at', 'label' => 'Último contacto', 'type' => 'datetime-local'],
            ['name' => 'follow_up_status', 'label' => 'Seguimiento', 'type' => 'select',
                'default' => 'active', 'help' => 'Pausado suspende los avisos; No contactar lo excluye hasta que lo reactives.',
                'options' => ['active' => 'Activo', 'paused' => 'Pausado', 'do_not_contact' => 'No contactar']],
            ['name' => 'next_contact_at', 'label' => 'Próximo contacto', 'type' => 'datetime-local',
                'help' => 'Si defines una fecha, no se avisará antes de ese momento.'],
            ['name' => 'address', 'label' => 'Dirección', 'type' => 'text', 'wide' => true],
            ['name' => 'notes', 'label' => 'Notas', 'type' => 'textarea', 'wide' => true],
        ];
    }

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
