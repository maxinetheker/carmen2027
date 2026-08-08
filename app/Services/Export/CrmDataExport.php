<?php

namespace App\Services\Export;

use App\Models\Appointment;
use App\Models\Contact;
use App\Models\ContactLog;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Property;
use App\Models\TaskItem;
use App\Support\CrmLabels;
use App\Support\PhoneNumber;

/** Volcado completo del CRM, una hoja por sección, para trabajar fuera del sistema. */
class CrmDataExport
{
    public const SECTIONS = [
        'leads' => 'Prospectos', 'contacts' => 'Contactos', 'properties' => 'Propiedades',
        'deals' => 'Oportunidades', 'appointments' => 'Agenda', 'tasks' => 'Tareas',
        'contact_logs' => 'Registro de contactos',
    ];

    public function build(array $sections): XlsxWorkbook
    {
        $book = new XlsxWorkbook;
        foreach (self::SECTIONS as $key => $name) {
            if (in_array($key, $sections, true)) {
                $this->{$key}($book->sheet($name));
            }
        }

        return $book;
    }

    private function table(XlsxSheet $sheet, array $headings, iterable $rows, array $widths): void
    {
        $sheet->widths($widths);
        $sheet->row(array_map(fn ($heading) => ['v' => $heading, 's' => 'head'], $headings));
        foreach ($rows as $row) {
            $sheet->row(array_map(fn ($value) => ['v' => $value, 's' => 'body'], array_values($row)));
        }
    }

    private function leads(XlsxSheet $sheet): void
    {
        $this->table($sheet, ['Nombre', 'Es', 'Teléfono', 'Correo', 'Origen', 'Etapa', 'Puntaje',
            'Presupuesto', 'Interés', 'Último contacto', 'Próximo contacto', 'Seguimiento'],
            Lead::orderBy('first_name')->cursor()->map(fn (Lead $lead) => [
                $lead->full_name, $lead->party_type_label, PhoneNumber::pretty($lead->phone),
                $lead->email ?: '', CrmLabels::get($lead->source), CrmLabels::get($lead->status),
                (int) $lead->score, $lead->budget ? (float) $lead->budget : '', $lead->interest ?: '',
                $this->date($lead->last_contact_at), $this->date($lead->next_contact_at),
                CrmLabels::get($lead->follow_up_status),
            ]), [26, 20, 16, 26, 16, 16, 10, 14, 30, 18, 18, 16]);
    }

    private function contacts(XlsxSheet $sheet): void
    {
        $this->table($sheet, ['Nombre', 'Es', 'Teléfono', 'Correo', 'Documento', 'Empresa',
            'Dirección', 'Guardado en el celular como', 'Último contacto', 'Próximo contacto', 'Seguimiento'],
            Contact::orderBy('first_name')->cursor()->map(fn (Contact $contact) => [
                $contact->full_name, $contact->party_type_label, PhoneNumber::pretty($contact->phone),
                $contact->email ?: '', $contact->document ?: '', $contact->company ?: '',
                $contact->address ?: '', $contact->device_contact_name ?: '',
                $this->date($contact->last_contact_at), $this->date($contact->next_contact_at),
                CrmLabels::get($contact->follow_up_status),
            ]), [26, 20, 16, 26, 14, 20, 28, 24, 18, 18, 16]);
    }

    private function properties(XlsxSheet $sheet): void
    {
        $this->table($sheet, ['Código', 'Título', 'Distrito', 'Tipo', 'Operación', 'Precio',
            'Moneda', 'Área m²', 'Dorm.', 'Baños', 'Estado', 'Publicada'],
            Property::orderBy('code')->cursor()->map(fn (Property $property) => [
                $property->code, $property->title, $property->district, $property->type_label,
                $property->operation_label, (float) $property->price, $property->currency,
                (float) $property->area, (int) $property->bedrooms, (float) $property->bathrooms,
                $property->status_label, $property->is_published ? 'Sí' : 'No',
            ]), [12, 42, 18, 16, 14, 16, 10, 12, 8, 8, 14, 12]);
    }

    private function deals(XlsxSheet $sheet): void
    {
        $this->table($sheet, ['Oportunidad', 'Etapa', 'Valor', 'Moneda', 'Probabilidad', 'Cierre estimado'],
            Deal::orderByDesc('id')->cursor()->map(fn (Deal $deal) => [
                $deal->title, CrmLabels::get($deal->stage), (float) $deal->value,
                $deal->currency, $deal->probability.'%', $this->date($deal->expected_close),
            ]), [42, 16, 16, 10, 14, 18]);
    }

    private function appointments(XlsxSheet $sheet): void
    {
        $this->table($sheet, ['Cita', 'Tipo', 'Inicio', 'Fin', 'Con quién', 'Propiedad', 'Lugar', 'Estado', 'Avisa'],
            Appointment::with(['contact', 'lead', 'property'])->orderByDesc('starts_at')->cursor()
                ->map(fn (Appointment $item) => [
                    $item->title, $item->type_label, $this->date($item->starts_at, true),
                    $this->date($item->ends_at, true), $item->person_name ?: '',
                    $item->property?->title ?: '', $item->location ?: '',
                    $item->status_label, $item->notify_enabled ? 'Sí' : 'No',
                ]), [36, 18, 18, 18, 24, 30, 26, 14, 8]);
    }

    private function tasks(XlsxSheet $sheet): void
    {
        $this->table($sheet, ['Tarea', 'Prioridad', 'Estado', 'Vence', 'Relacionada con', 'Avisa'],
            TaskItem::orderByRaw('due_at is null, due_at')->cursor()->map(fn (TaskItem $task) => [
                $task->title, $task->priority_label, $task->status_label,
                $this->date($task->due_at, true),
                $task->related_label ? $task->related_type_label.': '.$task->related_label : '',
                $task->notify_enabled ? 'Sí' : 'No',
            ]), [42, 14, 14, 18, 30, 8]);
    }

    private function contact_logs(XlsxSheet $sheet): void
    {
        $this->table($sheet, ['Cuándo', 'Persona', 'Medio', 'Sentido', 'Resultado', 'Duración', 'Origen', 'Notas'],
            ContactLog::with('subject')->orderByDesc('contacted_at')->limit(5000)->cursor()
                ->map(fn (ContactLog $log) => [
                    $this->date($log->contacted_at, true), $log->subject?->full_name ?: '',
                    $log->channel_label, $log->direction === 'incoming' ? 'Entrante' : 'Saliente',
                    $log->outcome_label ?: '', $log->duration_label ?: '',
                    $log->source === 'call_log' ? 'Registro del celular' : 'Manual',
                    $log->notes ?: '',
                ]), [18, 26, 14, 12, 20, 14, 20, 50]);
    }

    private function date($value, bool $withTime = false): string
    {
        return $value ? $value->format($withTime ? 'd/m/Y H:i' : 'd/m/Y') : '';
    }
}
