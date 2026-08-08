<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactLog;
use App\Services\PeopleDirectory;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Puente entre la agenda del celular y el CRM: revisar qué números ya existen,
 * dar de alta los elegidos y volcar el registro de llamadas sobre las personas
 * que ya están registradas.
 */
class PhoneSyncController extends Controller
{
    public function __construct(private PeopleDirectory $directory)
    {
    }

    /** Antes de importar, la app muestra cuáles ya están en el CRM y cuáles no. */
    public function preview(Request $request)
    {
        $data = $request->validate([
            'entries' => ['required', 'array', 'max:2000'],
            'entries.*.phone' => ['required', 'string', 'max:40'],
            'entries.*.name' => ['nullable', 'string', 'max:120'],
        ]);

        return response()->json(['data' => $this->directory->match($data['entries'])]);
    }

    /** Alta masiva de los contactos que la asesora marcó en la app. */
    public function importContacts(Request $request)
    {
        $data = $request->validate([
            'entries' => ['required', 'array', 'max:500'],
            'entries.*.phone' => ['required', 'string', 'max:40'],
            'entries.*.name' => ['required', 'string', 'max:120'],
            'entries.*.email' => ['nullable', 'email', 'max:120'],
            'party_type' => ['nullable', 'in:buyer,seller,both,other'],
        ]);

        $created = 0;
        $skipped = 0;
        foreach ($data['entries'] as $entry) {
            if ($this->directory->find($entry['phone'])) {
                $skipped++;

                continue;
            }
            [$first, $last] = $this->splitName($entry['name']);
            Contact::create([
                'first_name' => $first, 'last_name' => $last,
                'phone' => $entry['phone'], 'email' => $entry['email'] ?? null,
                'party_type' => $data['party_type'] ?? 'buyer',
                'device_contact_name' => $entry['name'],
                'notes' => 'Importado desde la agenda del celular.',
            ]);
            $created++;
        }

        return response()->json([
            'created' => $created, 'skipped' => $skipped,
            'message' => "Se agregaron {$created} contacto(s). {$skipped} ya existían.",
        ]);
    }

    /**
     * Volcado del registro de llamadas. Solo se guardan las llamadas de números
     * que ya están en el CRM: importar todo el historial del celular llenaría la
     * bitácora de bancos, taxis y desconocidos.
     */
    public function importCalls(Request $request)
    {
        $data = $request->validate([
            'calls' => ['required', 'array', 'max:2000'],
            'calls.*.phone' => ['required', 'string', 'max:40'],
            'calls.*.happened_at' => ['required', 'date'],
            'calls.*.duration_seconds' => ['nullable', 'integer', 'between:0,86400'],
            'calls.*.direction' => ['nullable', 'in:outgoing,incoming,missed'],
            'calls.*.device_contact_name' => ['nullable', 'string', 'max:120'],
        ]);

        $imported = 0;
        $duplicates = 0;
        $unknown = [];
        foreach ($data['calls'] as $call) {
            $person = $this->directory->find($call['phone']);
            if (! $person) {
                $unknown[] = $call['phone'];

                continue;
            }
            $at = Carbon::parse($call['happened_at']);
            $reference = 'call:'.PhoneNumber::key($call['phone']).':'.$at->timestamp;
            if (ContactLog::where('external_ref', $reference)->exists()) {
                $duplicates++;

                continue;
            }
            $missed = ($call['direction'] ?? 'outgoing') === 'missed';
            $person->contactLogs()->create([
                'channel' => 'call',
                'direction' => $missed ? 'incoming' : ($call['direction'] ?? 'outgoing'),
                'outcome' => $missed ? 'no_answer' : (($call['duration_seconds'] ?? 0) > 0 ? 'answered' : 'no_answer'),
                'phone_number' => $call['phone'],
                'device_contact_name' => $call['device_contact_name'] ?? null,
                'duration_seconds' => $call['duration_seconds'] ?? null,
                'contacted_at' => $at,
                'source' => 'call_log',
                'external_ref' => $reference,
                'user_id' => $request->user()?->id,
            ]);
            if (! empty($call['device_contact_name']) && ! $person->device_contact_name) {
                $person->update(['device_contact_name' => $call['device_contact_name']]);
            }
            $imported++;
        }

        return response()->json([
            'imported' => $imported,
            'duplicates' => $duplicates,
            'unknown' => count(array_unique($unknown)),
            'message' => "Se registraron {$imported} llamada(s). {$duplicates} ya estaban y "
                .count(array_unique($unknown)).' número(s) no están en el CRM.',
        ]);
    }

    /** @return array{0: string, 1: string|null} */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [$name];

        return [$parts[0], $parts[1] ?? null];
    }
}
