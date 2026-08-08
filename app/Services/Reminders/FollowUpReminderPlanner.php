<?php

namespace App\Services\Reminders;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\NotificationSetting;
use App\Support\HumanDate;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Avisos de "a quién toca llamar hoy".
 *
 * Salen dos cosas distintas: un aviso individual en cuanto llega la fecha de
 * próximo contacto que alguien dejó agendada —ese abre la ficha de la persona en
 * la app— y una lista diaria con todos los que llevan demasiado tiempo sin
 * actividad, para revisar de corrido.
 */
class FollowUpReminderPlanner
{
    /** @return Reminder[] */
    public function plan(NotificationSetting $setting, bool $listIsDue): array
    {
        if (! $setting->wants('follow_up')) {
            return [];
        }

        $people = $this->duePeople($setting);
        if ($people->isEmpty()) {
            return [];
        }

        $reminders = [];
        foreach ($people as $person) {
            if ($reminder = $this->scheduledCall($setting, $person)) {
                $reminders[] = $reminder;
            }
        }

        if ($listIsDue) {
            $reminders[] = $this->dailyList($setting, $people);
        }

        return $reminders;
    }

    /** @return Collection<int, Model> */
    private function duePeople(NotificationSetting $setting): Collection
    {
        $days = max(0, (int) $setting->follow_up_days);

        return Lead::query()->whereNotIn('status', ['won', 'lost'])
            ->dueForFollowUp($days)->orderBy('last_contact_at')->limit(100)->get()
            ->concat(Contact::query()->dueForFollowUp($days)
                ->orderBy('last_contact_at')->limit(100)->get())
            ->filter(fn (Model $person) => $person->wantsReminders())
            ->values();
    }

    /**
     * Cita telefónica que la propia asesora agendó: se avisa una sola vez, en el
     * momento, sin esperar a la lista diaria.
     */
    private function scheduledCall(NotificationSetting $setting, Model $person): ?Reminder
    {
        $when = $person->next_contact_at;
        if (! $when || $when->gt(now()) || $when->lt(now()->subDay())) {
            return null;
        }

        $isLead = $person instanceof Lead;
        $kind = $isLead ? 'Prospecto' : 'Contacto';
        $url = $isLead
            ? route('admin.leads.edit', $person)
            : route('admin.contacts.edit', $person);

        return Reminder::forRecord(
            type: 'follow_up',
            subject: $person,
            dedupeKey: sha1('follow_up|'.$person::class."|{$person->id}|{$when->timestamp}"),
            heading: 'Toca llamar a '.$person->full_name,
            intro: "Dejaste agendado contactar a {$person->full_name} ({$kind}, "
                .mb_strtolower($person->party_type_label).') el '
                .HumanDate::short($when, $setting->timezone).'.',
            item: $this->itemFor($setting, $person, $url),
            pushTitle: '📞 Llamar a '.$person->full_name,
            pushBody: $kind.' · '.$person->party_type_label.' · '.PhoneNumber::pretty($person->phone),
            pushData: [
                'route' => ($isLead ? 'leads/' : 'contacts/').$person->id,
                'record_id' => (string) $person->id,
                'phone' => (string) $person->phone,
                'url' => $url,
            ],
            scheduledFor: $when,
            urgency: 'now',
        );
    }

    private function dailyList(NotificationSetting $setting, Collection $people): Reminder
    {
        $items = $people->map(function (Model $person) use ($setting) {
            $isLead = $person instanceof Lead;

            return $this->itemFor($setting, $person, $isLead
                ? route('admin.leads.edit', $person)
                : route('admin.contacts.edit', $person));
        })->all();

        $buyers = $people->filter(fn ($p) => in_array($p->party_type, ['buyer', 'both'], true))->count();
        $sellers = $people->filter(fn ($p) => in_array($p->party_type, ['seller', 'both'], true))->count();

        return new Reminder(
            type: 'follow_up',
            heading: 'Clientes por contactar hoy ('.$people->count().')',
            intro: "Personas sin actividad registrada en {$setting->follow_up_days} días o con "
                ."contacto agendado para hoy. {$buyers} compran, {$sellers} venden.",
            items: $items,
            pushTitle: '📋 '.$people->count().' clientes por contactar',
            pushBody: $buyers.' compradores · '.$sellers.' vendedores · toca para ver la lista',
            pushData: ['route' => 'follow-ups', 'url' => route('admin.contacts.index')],
            dedupeKey: sha1('follow_up|list|'.$setting->now()->toDateString()),
            scheduledFor: now(),
        );
    }

    private function itemFor(NotificationSetting $setting, Model $person, string $url): array
    {
        $last = $person->last_contact_at
            ? 'Último contacto '.HumanDate::short($person->last_contact_at, $setting->timezone)
                .' ('.HumanDate::distance($person->last_contact_at, $setting->timezone).')'
            : 'Nunca se registró un contacto';

        return [
            'title' => $person->full_name.' · '.$person->party_type_label,
            'meta' => PhoneNumber::pretty($person->phone).' · '.$last,
            'detail' => $person->next_contact_at
                ? 'Contacto agendado para '.HumanDate::short($person->next_contact_at, $setting->timezone)
                : null,
            'url' => $url,
            'action' => 'Abrir la ficha',
        ];
    }
}
