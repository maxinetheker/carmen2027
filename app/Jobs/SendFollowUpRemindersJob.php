<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\NotificationSetting;
use App\Notifications\CrmReminderDigest;
use App\Jobs\Concerns\SendsReminderMail;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Builder;

class SendFollowUpRemindersJob
{
    use Queueable, SendsReminderMail;

    public function __construct(public int $settingId)
    {
    }

    public function handle(): bool
    {
        $setting = NotificationSetting::findOrFail($this->settingId);
        $cutoff = now()->subDays($setting->follow_up_days);
        $leads = $this->due(Lead::query()->whereNotIn('status', ['won', 'lost']), $cutoff)
            ->orderBy('last_contact_at')->limit(100)->get();
        $contacts = $this->due(Contact::query(), $cutoff)
            ->orderBy('last_contact_at')->limit(100)->get();

        if ($leads->isEmpty() && $contacts->isEmpty()) return false;
        $items = $leads->map(fn (Lead $lead) => [
            'title' => $lead->full_name,
            'meta' => ($lead->last_contact_at
                ? 'Último contacto: '.$lead->last_contact_at->format('d/m/Y H:i')
                : 'Todavía no se registró un contacto').' · '.$lead->phone,
            'url' => route('admin.leads.edit', $lead),
        ])->concat($contacts->map(fn (Contact $contact) => [
            'title' => $contact->full_name,
            'meta' => ($contact->last_contact_at
                ? 'Último contacto: '.$contact->last_contact_at->format('d/m/Y H:i')
                : 'Todavía no se registró un contacto').' · '.$contact->phone,
            'url' => route('admin.contacts.edit', $contact),
        ]))->all();
        $this->sendToRecipients($setting, new CrmReminderDigest('Clientes por contactar',
            "Prospectos y contactos sin actividad durante {$setting->follow_up_days} días o más.", $items));

        return true;
    }

    private function due(Builder $query, $cutoff): Builder
    {
        return $query->where('follow_up_status', 'active')
            ->where(function ($due) use ($cutoff) {
                $due->where('next_contact_at', '<=', now())
                    ->orWhere(function ($automatic) use ($cutoff) {
                        $automatic->whereNull('next_contact_at')
                            ->where(function ($inactive) use ($cutoff) {
                                $inactive->where('last_contact_at', '<=', $cutoff)
                                    ->orWhere(fn ($never) => $never->whereNull('last_contact_at')
                                        ->where('created_at', '<=', $cutoff));
                            });
                    });
            });
    }
}
