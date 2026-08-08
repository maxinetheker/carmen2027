<?php

namespace App\Support;

use App\Models\Contact;

/**
 * Campos que prospectos y contactos comparten palabra por palabra. Tenerlos en un
 * solo sitio evita que las dos pantallas se vayan separando con el tiempo, que es
 * justo lo que hacía difícil entender en qué se diferencian.
 */
class PeopleFields
{
    /** @return array<int, array<string, mixed>> */
    public static function identity(): array
    {
        return [
            ['name' => 'first_name', 'label' => 'Nombre', 'type' => 'text'],
            ['name' => 'last_name', 'label' => 'Apellidos', 'type' => 'text'],
            ['name' => 'phone', 'label' => 'Teléfono / WhatsApp', 'type' => 'tel'],
            ['name' => 'email', 'label' => 'Correo', 'type' => 'email'],
            ['name' => 'party_type', 'label' => '¿De qué lado está?', 'type' => 'select',
                'default' => 'buyer', 'options' => Contact::PARTY_TYPES,
                'help' => 'Comprador = busca propiedad. Vendedor = tiene una para captar. Ambos = las dos cosas.'],
            ['name' => 'interest', 'label' => 'Qué busca o qué ofrece', 'type' => 'text', 'wide' => true,
                'help' => 'Ej.: «Departamento 3 dorm. en San Isidro» o «Vende casa en Surco». Aparece en el resumen semanal.'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function followUp(): array
    {
        return [
            ['name' => 'last_contact_at', 'label' => 'Último contacto', 'type' => 'datetime-local',
                'help' => 'Se actualiza solo cada vez que registras una llamada o mensaje abajo.'],
            ['name' => 'next_contact_at', 'label' => 'Próximo contacto', 'type' => 'datetime-local',
                'help' => 'Si pones fecha y hora, recibirás un aviso justo en ese momento.'],
            ['name' => 'follow_up_status', 'label' => 'Seguimiento', 'type' => 'select',
                'default' => 'active', 'options' => Contact::FOLLOW_UP_STATUSES,
                'help' => 'Pausado suspende los avisos; No contactar lo excluye hasta que lo reactives.'],
            ['name' => 'notify_email', 'label' => 'Avisarme por correo', 'type' => 'checkbox'],
            ['name' => 'notify_push', 'label' => 'Avisarme en la app', 'type' => 'checkbox'],
            ['name' => 'notes', 'label' => 'Notas', 'type' => 'textarea', 'wide' => true],
        ];
    }

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'first_name' => ['required', 'max:80'], 'last_name' => ['nullable', 'max:80'],
            'email' => ['nullable', 'email', 'max:120'], 'phone' => ['required', 'max:30'],
            'party_type' => ['required', 'in:buyer,seller,both,other'],
            'interest' => ['nullable', 'max:160'],
            'last_contact_at' => ['nullable', 'date'],
            'follow_up_status' => ['required', 'in:active,paused,do_not_contact'],
            'next_contact_at' => ['nullable', 'date'],
            'notify_email' => ['nullable', 'boolean'],
            'notify_push' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'max:5000'],
        ];
    }
}
