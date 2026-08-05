<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyFeature extends Model
{
    public const ICONS = [
        'info' => 'Información',
        'verified' => 'Verificado',
        'description' => 'Documento',
        'account_balance' => 'Registros públicos',
        'gavel' => 'Situación legal',
        'straighten' => 'Medida',
        'square_foot' => 'Área',
        'bed' => 'Dormitorio',
        'bathtub' => 'Baño',
        'wash' => 'Baño de visita',
        'garage' => 'Cochera',
        'elevator' => 'Ascensor',
        'local_parking' => 'Estacionamiento',
        'dining' => 'Comedor',
        'kitchen' => 'Cocina',
        'weekend' => 'Sala de estar',
        'chair' => 'Sala',
        'desk' => 'Estudio o área de trabajo',
        'business_center' => 'Oficina',
        'outdoor_grill' => 'Área de parrillas',
        'local_laundry_service' => 'Lavandería',
        'checkroom' => 'Walk-in closet',
        'pool' => 'Piscina',
        'hot_tub' => 'Jacuzzi',
        'fitness_center' => 'Gimnasio',
        'deck' => 'Terraza o patio',
        'balcony' => 'Balcón',
        'yard' => 'Jardín',
        'potted_plant' => 'Jardín interior',
        'meeting_room' => 'Sala de reuniones',
        'groups' => 'Club house',
        'sports_esports' => 'Salón de juegos',
        'sports_soccer' => 'Área deportiva',
        'security' => 'Seguridad o guardianía',
        'door_front' => 'Hall de ingreso',
        'garage_door' => 'Portón eléctrico',
        'payments' => 'Financiamiento',
        'map' => 'Zonificación',
        'water_drop' => 'Agua, cisterna o tanque',
        'electric_bolt' => 'Electricidad',
        'electrical_services' => 'Pozo a tierra',
        'ac_unit' => 'Aire acondicionado',
        'mode_heat' => 'Calefacción',
        'fireplace' => 'Chimenea',
        'inventory_2' => 'Depósito o almacén',
        'iron' => 'Cuarto de planchado',
        'stairs' => 'Pisos o escaleras',
        'room_service' => 'Servicios',
        'pets' => 'Mascotas',
        'location_on' => 'Ubicación',
        'apartment' => 'Edificación',
        'landscape' => 'Terreno',
    ];

    public const PRESETS = [
        'account_balance' => 'Inscripción registral',
        'gavel' => 'Libre de cargas',
        'payments' => 'Financiamiento',
        'map' => 'Zonificación',
        'straighten' => 'Frente',
        'landscape' => 'Fondo',
        'garage' => 'Cocheras',
        'elevator' => 'Ascensor',
        'pets' => 'Mascotas',
        'outdoor_grill' => 'Área de parrillas',
        'local_laundry_service' => 'Lavandería',
        'kitchen' => 'Cocina',
        'deck' => 'Terraza',
        'yard' => 'Jardín',
        'pool' => 'Piscina',
        'fitness_center' => 'Gimnasio',
        'inventory_2' => 'Depósito',
        'security' => 'Seguridad',
        'ac_unit' => 'Aire acondicionado',
        'meeting_room' => 'Sala de reuniones',
    ];

    protected $guarded = [];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
