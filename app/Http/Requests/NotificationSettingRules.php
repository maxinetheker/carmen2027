<?php

namespace App\Http\Requests;

use App\Models\NotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Reglas y casillas compartidas por el panel web y la API de la app: si solo
 * estuvieran en uno de los dos, guardar desde el celular borraría ajustes que
 * únicamente existen en el navegador.
 */
class NotificationSettingRules
{
    public const TIMEZONES = ['America/Lima' => 'Lima', 'America/Bogota' => 'Bogotá',
        'America/Mexico_City' => 'Ciudad de México', 'America/New_York' => 'Nueva York'];

    /** @return array<int, string> */
    public static function booleans(): array
    {
        $names = ['overdue_enabled', 'task_notify_default', 'appointment_notify_default',
            'appointment_exact_enabled', 'task_exact_enabled',
            'appointment_immediate_enabled', 'task_immediate_enabled'];
        foreach (NotificationSetting::TYPES as $type) {
            $names[] = "{$type}_enabled";
            $names[] = "{$type}_email_enabled";
            $names[] = "{$type}_push_enabled";
        }

        return $names;
    }

    /** @return array<string, mixed> */
    public static function shared(): array
    {
        $rules = [
            'timezone' => ['required', Rule::in(array_keys(self::TIMEZONES))],
            'overdue_days' => ['required', 'integer', 'between:0,60'],
        ];
        foreach (self::booleans() as $name) {
            $rules[$name] = ['nullable', 'boolean'];
        }
        foreach (NotificationSetting::TYPES as $type) {
            $rules["{$type}_frequency"] = ['required', Rule::in(['daily', 'weekly'])];
            $rules["{$type}_time"] = ['required', 'date_format:H:i'];
            $rules["{$type}_weekday"] = ['required', 'integer', 'between:1,7'];
            $rules["{$type}_days"] = ['required', 'integer', 'between:0,365'];
        }
        foreach (['appointment', 'task'] as $type) {
            $rules["{$type}_lead_minutes"] = ['required', 'integer', 'between:5,10080'];
        }

        return $rules;
    }

    /** Las casillas no marcadas no llegan en el request; hay que leerlas explícitamente. */
    public static function withBooleans(array $data, Request $request): array
    {
        foreach (self::booleans() as $name) {
            $data[$name] = $request->boolean($name);
        }

        return $data;
    }
}
