<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'hero_eyebrow' => 'Carmen Mestanza · Experiencia y cercanía en Lima',
            'hero_title' => 'Tu asesora inmobiliaria de confianza, en cada decisión.',
            'hero_subtitle' => 'Compra, vende o alquila con información clara, estrategia y el acompañamiento directo de Carmen de principio a fin.',
            'phone' => '+51 987 654 321',
            'whatsapp' => '51987654321',
            'email' => 'carmen@mestanzainmobiliaria.pe',
            'ceo_title' => 'Directora y asesora inmobiliaria',
            'ceo_bio' => 'Carmen combina conocimiento del mercado limeño, negociación y un trato cercano para convertir decisiones complejas en procesos claros y seguros.',
            'service_area' => 'Miraflores · San Isidro · Barranco · Surco · La Molina',
            'seo_title' => 'Carmen Mestanza · Tu asesora inmobiliaria de confianza en Lima',
            'seo_description' => 'Compra, vende o alquila propiedades en Lima con Carmen Mestanza, tu asesora de confianza. Acompañamiento cercano, estrategia y claridad de principio a fin.',
        ];
        foreach ($settings as $key => $value) {
            SiteSetting::firstOrCreate(['key' => $key], [
                'value' => $value,
                'group' => str_starts_with($key, 'hero') ? 'hero'
                    : (str_starts_with($key, 'seo') ? 'seo' : 'general'),
                'type' => str_contains($key, 'bio') || str_contains($key, 'subtitle')
                    || str_contains($key, 'description') ? 'textarea' : 'text',
            ]);
        }

        if (! app()->environment('testing') && ! config('crm.seed_demo_data', false)) {
            return;
        }

        $properties = [
            ['CM-101', 'Ático con terraza panorámica', 'departamento', 'venta', 'Miraflores', 485000, 3, 3.5, 214, 'property-1.jpg', -12.1211, -77.0297],
            ['CM-102', 'Residencia contemporánea en parque', 'casa', 'venta', 'La Molina', 760000, 4, 4.5, 420, 'property-2.jpg', -12.0820, -76.9282],
            ['CM-103', 'Departamento de diseño junto al malecón', 'departamento', 'alquiler', 'Barranco', 2400, 2, 2, 126, 'property-3.jpg', -12.1490, -77.0208],
            ['CM-104', 'Oficina premium con vista urbana', 'oficina', 'alquiler', 'San Isidro', 3200, 0, 2, 168, 'property-1.jpg', -12.0970, -77.0369],
            ['CM-105', 'Departamento familiar frente a parque', 'departamento', 'venta', 'Santiago de Surco', 289000, 3, 2.5, 156, 'property-2.jpg', -12.1416, -76.9918],
            ['CM-106', 'Dúplex minimalista con terraza superior', 'departamento', 'venta', 'Barranco', 399000, 3, 3, 188, 'property-3.jpg', -12.1510, -77.0185],
        ];
        foreach ($properties as $index => $item) {
            [$code, $title, $type, $operation, $district, $price, $beds, $baths, $area, $image, $latitude, $longitude] = $item;
            $property = Property::firstOrCreate(['code' => $code], [
                'title' => $title, 'slug' => str($title.'-'.$code)->slug(),
                'type' => $type, 'operation' => $operation, 'district' => $district,
                'price' => $price, 'currency' => 'USD', 'bedrooms' => $beds,
                'bathrooms' => $baths, 'area' => $area, 'status' => 'available',
                'featured' => $index < 3, 'image_url' => '/images/'.$image,
                'latitude' => $latitude, 'longitude' => $longitude,
                'is_published' => true, 'show_in_hero' => $index < 3,
                'priority' => max(0, 100 - ($index * 10)),
                'description' => '<p>Una propiedad seleccionada por su ubicación, distribución y potencial de valorización.</p><h3>Una visita pensada para ti</h3><p>Agenda un recorrido privado y recibe la información completa para tomar una decisión con claridad.</p>',
            ]);
            foreach ([
                ['verified', 'Visitas', 'Previa coordinación'],
                ['description', 'Ficha técnica', 'Disponible a solicitud'],
                ['info', 'Código', $code],
            ] as $featureIndex => [$icon, $label, $value]) {
                $property->features()->firstOrCreate(['label' => $label], [
                    'icon' => $icon, 'value' => $value, 'sort_order' => $featureIndex,
                ]);
            }
        }
    }
}
