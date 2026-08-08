<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Lead;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Índice de personas del CRM por número de teléfono.
 *
 * La app manda números tal como están en la agenda del celular ("+51 987…",
 * "987654321", "051987654321"); buscar por igualdad exacta no encontraría casi
 * nada, así que aquí se compara la forma normalizada. Se carga una sola vez por
 * petición porque la sincronización recorre cientos de contactos de golpe.
 */
class PeopleDirectory
{
    /** @var Collection<string, Model>|null */
    private ?Collection $index = null;

    /** Contactos primero: si alguien está en ambos lados, gana su ficha completa. */
    public function find(?string $phone): ?Model
    {
        $key = PhoneNumber::key($phone);

        return $key ? $this->index()->get($key) : null;
    }

    /** @return Collection<string, Model> */
    public function index(): Collection
    {
        if ($this->index !== null) {
            return $this->index;
        }

        $index = collect();
        foreach ([Lead::query()->cursor(), Contact::query()->cursor()] as $people) {
            foreach ($people as $person) {
                if ($key = PhoneNumber::key($person->phone)) {
                    $index[$key] = $person;
                }
            }
        }

        return $this->index = $index;
    }

    /**
     * Marca cuáles de los números que llegan desde el celular ya existen.
     *
     * @param  array<int, array{phone: string, name?: string|null}>  $entries
     * @return array<int, array<string, mixed>>
     */
    public function match(array $entries): array
    {
        return collect($entries)->map(function (array $entry) {
            $person = $this->find($entry['phone'] ?? null);

            return [
                'phone' => $entry['phone'] ?? '',
                'name' => $entry['name'] ?? null,
                'exists' => (bool) $person,
                'crm_type' => $person instanceof Lead ? 'lead' : ($person ? 'contact' : null),
                'crm_id' => $person?->getKey(),
                'crm_name' => $person?->full_name,
                'device_contact_name' => $person?->device_contact_name,
            ];
        })->all();
    }
}
