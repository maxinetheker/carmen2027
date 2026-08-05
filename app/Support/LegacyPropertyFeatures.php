<?php

namespace App\Support;

use App\Models\Property;

final class LegacyPropertyFeatures
{
    private const BOOLEAN = [
        'salon_de_juegos' => ['sports_esports', 'Salón de juegos'], 'cocina' => ['kitchen', 'Cocina'],
        'salas' => ['chair', 'Sala'], 'terraza' => ['deck', 'Terraza'], 'club_house' => ['groups', 'Club house'],
        'area_de_parrillas' => ['outdoor_grill', 'Área de parrillas'], 'dormitorio_de_servicio' => ['bed', 'Dormitorio de servicio'],
        'gimnasio' => ['fitness_center', 'Gimnasio'], 'area_deportiva' => ['sports_soccer', 'Área deportiva'],
        'area_de_trabajo' => ['desk', 'Área de trabajo'], 'inscrip_en_registros_publicos' => ['account_balance', 'Inscripción registral'],
        'bano_de_visita' => ['wash', 'Baño de visita'], 'comedor_de_diario' => ['dining', 'Comedor de diario'],
        'sala_de_estar' => ['weekend', 'Sala de estar'], 'sala_de_reuniones' => ['meeting_room', 'Sala de reuniones'],
        'patio' => ['deck', 'Patio'], 'patio_trasero' => ['deck', 'Patio trasero'], 'guardania' => ['security', 'Guardianía'],
        'ascensor' => ['elevator', 'Ascensor'], 'porton_electrico' => ['garage_door', 'Portón eléctrico'],
        'cisterna' => ['water_drop', 'Cisterna'], 'tanque_elevado' => ['water_drop', 'Tanque elevado'],
        'pozo_a_tierra' => ['electrical_services', 'Pozo a tierra'], 'libre_de_cargas_gravamenes' => ['gavel', 'Libre de cargas'],
        'comedor' => ['dining', 'Comedor'], 'lavanderia' => ['local_laundry_service', 'Lavandería'],
        'cuarto_de_planchado' => ['iron', 'Cuarto de planchado'], 'hall_ingreso' => ['door_front', 'Hall de ingreso'],
        'walk_in_closet' => ['checkroom', 'Walk-in closet'], 'permite_mascotas' => ['pets', 'Permite mascotas'],
        'listos_para_ser_financiado' => ['payments', 'Apto para financiamiento'], 'piscina' => ['pool', 'Piscina'],
        'oficina' => ['business_center', 'Oficina'], 'escritorio' => ['desk', 'Escritorio'], 'estudio' => ['desk', 'Estudio'],
        'jacuzzi' => ['hot_tub', 'Jacuzzi'], 'jardin_interior' => ['potted_plant', 'Jardín interior'], 'jardin' => ['yard', 'Jardín'],
        'chimenea' => ['fireplace', 'Chimenea'], 'calefaccion' => ['mode_heat', 'Calefacción'],
        'aire_acondicionado' => ['ac_unit', 'Aire acondicionado'], 'almacen_de_alimentos' => ['inventory_2', 'Almacén de alimentos'],
        'deposito' => ['inventory_2', 'Depósito'],
    ];

    public function create(Property $property, array $detail): void
    {
        $features = [];
        foreach (self::BOOLEAN as $field => [$icon, $label]) {
            if ($this->enabled($detail[$field] ?? null)) $features[] = compact('icon', 'label') + ['value' => 'Sí'];
        }
        foreach ([
            'cocheras' => ['garage', 'Cocheras', ''], 'pisos' => ['stairs', 'Pisos', ''],
            'frente_metros' => ['straighten', 'Frente', ' m'], 'fondo_metros' => ['landscape', 'Fondo', ' m'],
            'zonificacion' => ['map', 'Zonificación', ''], 'servicios' => ['room_service', 'Servicios', ''],
        ] as $field => [$icon, $label, $suffix]) {
            $value = trim((string) ($detail[$field] ?? ''));
            if ($value !== '' && $value !== '0' && mb_strtolower($value) !== 'no definido') {
                $features[] = compact('icon', 'label') + ['value' => $value.$suffix];
            }
        }
        foreach ($features as $order => $feature) {
            $property->features()->create($feature + ['sort_order' => $order]);
        }
    }

    private function enabled(mixed $value): bool
    {
        return in_array(mb_strtolower(trim((string) $value)), ['1', 'sí', 'si', 'yes', 'true'], true);
    }
}
