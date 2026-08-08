<?php

namespace App\Services\Reminders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Un aviso listo para salir. Guarda por separado el texto largo (correo) y el
 * corto (notificación del celular) porque una notificación que solo dice
 * "Tareas pendientes" no le sirve de nada a quien la recibe en la calle: tiene
 * que decir qué tarea es, para cuándo y si ya venció.
 */
final class Reminder
{
    /**
     * @param  array<int, array{title: string, meta: string, url: string}>  $items
     * @param  array<string, string>  $pushData
     */
    public function __construct(
        public string $type,
        public string $heading,
        public string $intro,
        public array $items,
        public string $pushTitle,
        public string $pushBody,
        public array $pushData = [],
        public ?string $dedupeKey = null,
        public ?Model $subject = null,
        public ?Carbon $scheduledFor = null,
        public string $urgency = 'normal',
    ) {
    }

    public static function forRecord(
        string $type,
        Model $subject,
        string $dedupeKey,
        string $heading,
        string $intro,
        array $item,
        string $pushTitle,
        string $pushBody,
        array $pushData,
        ?Carbon $scheduledFor = null,
        string $urgency = 'normal',
    ): self {
        return new self(
            type: $type,
            heading: $heading,
            intro: $intro,
            items: [$item],
            pushTitle: $pushTitle,
            pushBody: $pushBody,
            pushData: $pushData,
            dedupeKey: $dedupeKey,
            subject: $subject,
            scheduledFor: $scheduledFor,
            urgency: $urgency,
        );
    }
}
