<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\LeadResource;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Support\PeopleFields;

class LeadController extends CrudController
{
    protected string $model = Lead::class;
    protected string $resourceClass = LeadResource::class;
    protected string $label = 'Prospecto';
    protected array $search = ['first_name', 'last_name', 'email', 'phone'];
    protected array $with = ['assignedTo'];

    protected function rules(?int $id = null): array
    {
        return PeopleFields::rules() + [
            'source' => ['required'], 'status' => ['required'],
            'score' => ['required', 'integer', 'between:0,100'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ];
    }

    public function convert(int $record)
    {
        $lead = Lead::findOrFail($record);
        $contact = Contact::firstOrCreate(
            ['phone' => $lead->phone],
            $lead->only('first_name', 'last_name', 'email', 'phone', 'notes', 'party_type',
                'last_contact_at', 'follow_up_status', 'next_contact_at',
                'notify_email', 'notify_push', 'device_contact_name')
        );
        $lead->contactLogs()->update([
            'subject_type' => Contact::class, 'subject_id' => $contact->id,
        ]);
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
