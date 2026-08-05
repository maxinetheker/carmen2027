<?php

namespace App\Support;

class CrmLabels
{
    private const VALUES = [
        'available' => 'Disponible', 'reserved' => 'Reservada', 'sold' => 'Vendida',
        'venta' => 'Venta', 'alquiler' => 'Alquiler',
        'new' => 'Nuevo', 'contacted' => 'Contactado', 'qualified' => 'Calificado',
        'nurturing' => 'En seguimiento', 'won' => 'Ganado', 'lost' => 'Perdido',
        'visit' => 'Visita', 'proposal' => 'Propuesta',
        'negotiation' => 'Negociación',
        'web' => 'Sitio web', 'referral' => 'Referido',
        'social' => 'Redes sociales', 'portal' => 'Portal inmobiliario',
        'manual' => 'Registro manual',
        'low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'urgent' => 'Urgente',
        'pending' => 'Pendiente', 'doing' => 'En curso', 'done' => 'Completada',
        'scheduled' => 'Programada', 'confirmed' => 'Confirmada',
        'cancelled' => 'Cancelada', 'call' => 'Llamada',
        'meeting' => 'Reunión', 'signing' => 'Firma',
        'lead' => 'Prospecto', 'contact' => 'Contacto',
        'property' => 'Propiedad', 'deal' => 'Oportunidad',
        'active' => 'Activo', 'paused' => 'Pausado', 'do_not_contact' => 'No contactar',
    ];

    public static function get(mixed $value): string
    {
        if ($value === null || $value === '') return '—';
        return self::VALUES[(string) $value] ?? ucfirst((string) $value);
    }
}
