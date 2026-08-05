<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\LeadResource;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;

class LeadController extends CrudController
{
    protected string $model = Lead::class;
    protected string $resourceClass = LeadResource::class;
    protected string $label = 'Prospecto';
    protected array $search = ['first_name', 'last_name', 'email', 'phone'];
    protected array $with = ['assignedTo'];

    protected function rules(?int $id = null): array
    {
        return [
            'first_name' => ['required', 'max:80'], 'last_name' => ['nullable', 'max:80'],
            'email' => ['nullable', 'email', 'max:120'], 'phone' => ['required', 'max:30'],
            'source' => ['required'], 'status' => ['required'], 'score' => ['required', 'integer', 'between:0,100'],
            'budget' => ['nullable', 'numeric', 'min:0'], 'interest' => ['nullable', 'max:160'],
            'assigned_to' => ['nullable', 'exists:users,id'], 'last_contact_at' => ['nullable', 'date'],
            'follow_up_status' => ['required', 'in:active,paused,do_not_contact'],
            'next_contact_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'max:5000'],
        ];
    }

    public function convert(int $record)
    {
        $lead = Lead::findOrFail($record);
        $contact = Contact::firstOrCreate(
            ['phone' => $lead->phone],
            $lead->only('first_name', 'last_name', 'email', 'phone', 'notes',
                'last_contact_at', 'follow_up_status', 'next_contact_at')
        );
        Deal::firstOrCreate(['lead_id' => $lead->id], [
            'contact_id' => $contact->id, 'owner_id' => $lead->assigned_to,
            'title' => 'Oportunidad · '.$lead->full_name, 'value' => $lead->budget ?? 0,
            'stage' => 'qualified', 'probability' => 25,
        ]);
        $lead->update(['status' => 'qualified']);
        $this->log($lead, 'converted', 'Prospecto convertido en contacto y oportunidad');

        return new LeadResource($lead->fresh($this->with));
    }
}
