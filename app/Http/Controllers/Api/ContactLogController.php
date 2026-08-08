<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContactLogResource;
use App\Models\Contact;
use App\Models\ContactLog;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactLogController extends Controller
{
    public function index(string $type, int $record)
    {
        return ContactLogResource::collection(
            $this->person($type, $record)->contactLogs()->with('user')->limit(100)->get()
        );
    }

    public function store(Request $request, string $type, int $record)
    {
        $person = $this->person($type, $record);
        $data = $request->validate([
            'channel' => ['required', Rule::in(array_keys(ContactLog::CHANNELS))],
            'direction' => ['required', Rule::in(['outgoing', 'incoming'])],
            'outcome' => ['nullable', Rule::in(array_keys(ContactLog::OUTCOMES))],
            'contacted_at' => ['required', 'date'],
            'duration_seconds' => ['nullable', 'integer', 'between:0,86400'],
            'notes' => ['nullable', 'max:2000'],
            'next_contact_at' => ['nullable', 'date'],
        ]);

        $log = $person->contactLogs()->create(
            collect($data)->except('next_contact_at')->all() + [
                'user_id' => $request->user()?->id,
                'phone_number' => $person->phone,
                'device_contact_name' => $person->device_contact_name,
                'source' => 'app',
            ]
        );

        if (! empty($data['next_contact_at'])) {
            $person->update(['next_contact_at' => $data['next_contact_at']]);
        }

        return (new ContactLogResource($log))->response()->setStatusCode(201);
    }

    public function destroy(ContactLog $log)
    {
        $log->delete();

        return response()->json(['message' => 'Registro eliminado.']);
    }

    private function person(string $type, int $record): Model
    {
        abort_unless(in_array($type, ['leads', 'contacts'], true), 404);

        return $type === 'leads'
            ? Lead::findOrFail($record)
            : Contact::findOrFail($record);
    }
}
