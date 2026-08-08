<?php

namespace App\Services\Export;

use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\TaskItem;
use App\Support\CrmLabels;
use App\Support\HumanDate;
use App\Support\PhoneNumber;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * El resumen semanal que se llevaba a mano en Excel, ahora armado desde el CRM:
 * mismos bloques de colores, más el último contacto y los días sin actividad.
 */
class WeeklySummaryExport
{
    private const WIDTHS = [30, 34, 18, 22, 26, 18];

    private SummaryBlock $block;

    public function build(Carbon $from, Carbon $to): XlsxWorkbook
    {
        $book = new XlsxWorkbook;
        $sheet = $book->sheet('Resumen semanal')->widths(self::WIDTHS);
        $this->block = new SummaryBlock($sheet, count(self::WIDTHS));

        $sheet->row([['v' => 'Resumen de la semana · '.$from->format('d/m/Y')
            .' al '.$to->format('d/m/Y'), 's' => 'title']]);
        $sheet->row([['v' => 'Generado el '.now()->format('d/m/Y H:i'), 's' => 'muted']]);
        $sheet->row([]);
        $this->buyers();
        $this->sellers();
        $this->visits($from, $to);
        $this->tasks($to);
        $this->toContact();

        return $book;
    }

    private function buyers(): void
    {
        $this->block->write('blue', 'Clientes compradores',
            ['Cliente', 'Qué busca', 'Teléfono', 'Último contacto', 'Próximo paso', 'Etapa'],
            $this->people(['buyer', 'both'])->map(fn ($person) => [
                $person->full_name,
                $person->interest ?: '—',
                PhoneNumber::pretty($person->phone),
                $this->lastContact($person),
                $person->next_contact_at ? HumanDate::short($person->next_contact_at) : 'Sin agendar',
                $person instanceof Lead ? CrmLabels::get($person->status) : 'Cartera',
            ]));
    }

    private function sellers(): void
    {
        $this->block->write('amber', 'Clientes vendedores · propiedades por captar',
            ['Propietario', 'Propiedad a captar', 'Teléfono', 'Último contacto', 'Cita de captación', 'Estado'],
            $this->people(['seller', 'both'])->map(fn ($person) => [
                $person->full_name,
                $person->interest ?: '—',
                PhoneNumber::pretty($person->phone),
                $this->lastContact($person),
                $this->captureDate($person),
                CrmLabels::get($person->follow_up_status),
            ]));
    }

    private function visits(Carbon $from, Carbon $to): void
    {
        $appointments = Appointment::with(['contact', 'lead', 'property'])
            ->whereBetween('starts_at', [$from, $to])
            ->orderBy('starts_at')->get();

        $this->block->write('green', 'Visitas y citas agendadas',
            ['Cita', 'Día y hora', 'Con quién', 'Propiedad', 'Lugar', 'Estado'],
            $appointments->map(fn (Appointment $item) => [
                $item->title,
                HumanDate::short($item->starts_at),
                $item->person_name ?: '—',
                $item->property?->title ?: '—',
                $item->location ?: 'Por confirmar',
                $item->status_label,
            ]));
    }

    private function tasks(Carbon $to): void
    {
        $tasks = TaskItem::where('status', '!=', 'done')
            ->where(fn ($query) => $query->whereNull('due_at')->orWhere('due_at', '<=', $to))
            ->orderByRaw('due_at is null, due_at')->get();

        $this->block->write('peach', 'Tareas pendientes',
            ['Tarea', 'Vence', 'Prioridad', 'Relacionada con', 'Estado', ''],
            $tasks->map(fn (TaskItem $task) => [
                $task->title,
                $task->due_at ? HumanDate::short($task->due_at) : 'Sin fecha',
                $task->priority_label,
                $task->related_label ? $task->related_type_label.': '.$task->related_label : '—',
                $task->due_at?->isPast() ? 'VENCIDA' : $task->status_label,
                '',
            ]));
    }

    private function toContact(): void
    {
        $days = 7;
        $people = Lead::dueForFollowUp($days)->get()
            ->concat(Contact::dueForFollowUp($days)->get())
            ->sortBy(fn ($person) => $person->last_contact_at?->timestamp ?? 0);

        $this->block->write('grey', 'Por contactar (carteo y seguimiento)',
            ['Persona', 'Es', 'Teléfono', 'Último contacto', 'Días sin contacto', 'Seguimiento'],
            $people->map(fn ($person) => [
                $person->full_name,
                $person->party_type_label,
                PhoneNumber::pretty($person->phone),
                $this->lastContact($person),
                $person->last_contact_at
                    ? (int) $person->last_contact_at->diffInDays(now()) : 'Nunca',
                CrmLabels::get($person->follow_up_status),
            ]));
    }

    /** @return Collection<int, mixed> */
    private function people(array $types): Collection
    {
        return Lead::whereIn('party_type', $types)->whereNotIn('status', ['lost'])->get()
            ->concat(Contact::whereIn('party_type', $types)->get())
            ->sortBy('full_name')->values();
    }

    private function lastContact($person): string
    {
        return $person->last_contact_at ? HumanDate::short($person->last_contact_at) : 'Nunca';
    }

    /** Próxima cita de captación agendada con esa persona, si es que hay una. */
    private function captureDate($person): string
    {
        $appointment = Appointment::where('type', 'capture')
            ->where($person instanceof Lead ? 'lead_id' : 'contact_id', $person->id)
            ->where('starts_at', '>=', now()->subDay())->orderBy('starts_at')->first();

        return $appointment ? HumanDate::short($appointment->starts_at) : 'Sin agendar';
    }
}
