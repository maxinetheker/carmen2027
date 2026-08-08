<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Contact;
use App\Models\ContactLog;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactLogController extends Controller
{
    /** Identifica el modal que hay que volver a abrir tras guardar o al fallar. */
    private const PANEL = 'contact-log';

    public function storeForLead(Request $request, int $record)
    {
        return $this->store($request, Lead::findOrFail($record));
    }

    public function storeForContact(Request $request, int $record)
    {
        return $this->store($request, Contact::findOrFail($record));
    }

    public function destroy(ContactLog $log)
    {
        $log->delete();

        return back()->with('success', 'Registro de contacto eliminado.')->with('openPanel', self::PANEL);
    }

    private function store(Request $request, Model $person)
    {
        // Bolsa propia: los errores de este formulario no deben aparecer como si
        // fueran del formulario de datos de la persona, que es otro <form>.
        $data = $request->validateWithBag(self::PANEL, [
            'channel' => ['required', Rule::in(array_keys(ContactLog::CHANNELS))],
            'direction' => ['required', Rule::in(['outgoing', 'incoming'])],
            'outcome' => ['nullable', Rule::in(array_keys(ContactLog::OUTCOMES))],
            'contacted_at' => ['required', 'date'],
            'duration_seconds' => ['nullable', 'integer', 'between:0,86400'],
            'notes' => ['nullable', 'max:2000'],
            'next_contact_at' => ['nullable', 'date'],
        ]);

        // `next_contact_at` es de la persona, no del registro de contacto.
        $person->contactLogs()->create(collect($data)->except('next_contact_at')->all() + [
            'user_id' => auth()->id(),
            'phone_number' => $person->phone,
            'device_contact_name' => $person->device_contact_name,
            'source' => 'manual',
        ]);

        // Agendar el siguiente paso desde el mismo formulario es lo que hace que el
        // seguimiento no se caiga: sin fecha, la persona solo reaparece cuando ya
        // pasaron demasiados días sin actividad.
        if ($request->filled('next_contact_at')) {
            $person->update(['next_contact_at' => $data['next_contact_at']]);
        }

        Activity::create([
            'user_id' => auth()->id(),
            'subject_type' => $person::class, 'subject_id' => $person->getKey(),
            'type' => 'contacted',
            'description' => ContactLog::CHANNELS[$data['channel']].' con '.$person->full_name,
            'happened_at' => now(),
        ]);

        return back()->with('success', 'Contacto registrado. Se actualizó la fecha de último contacto.')
            ->with('openPanel', self::PANEL);
    }
}
