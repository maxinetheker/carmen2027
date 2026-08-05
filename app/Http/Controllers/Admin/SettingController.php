<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    private array $fields = [
        'hero_eyebrow' => ['Etiqueta principal', 'text'],
        'hero_title' => ['Título principal', 'text'],
        'hero_subtitle' => ['Texto principal', 'textarea'],
        'phone' => ['Teléfono', 'text'],
        'whatsapp' => ['WhatsApp', 'text'],
        'email' => ['Correo', 'email'],
        'ceo_title' => ['Cargo de Carmen', 'text'],
        'ceo_bio' => ['Biografía de la directora', 'textarea'],
        'service_area' => ['Zona de atención', 'text'],
        'seo_title' => ['Título SEO principal', 'text'],
        'seo_description' => ['Descripción SEO principal', 'textarea'],
    ];

    public function edit()
    {
        return view('admin.settings', [
            'fields' => $this->fields,
            'settings' => SiteSetting::values() + [
                'seo_title' => 'Carmen Mestanza · Tu asesora inmobiliaria de confianza en Lima',
                'seo_description' => 'Compra, vende o alquila propiedades en Lima con Carmen Mestanza, tu asesora de confianza. Acompañamiento cercano, estrategia y claridad de principio a fin.',
            ],
        ]);
    }

    public function update(Request $request)
    {
        $rules = collect($this->fields)->mapWithKeys(
            fn ($config, $key) => [$key => ['nullable', 'string', 'max:3000']]
        )->all();
        $data = $request->validate($rules + [
            'seo_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],
        ]);

        foreach (array_intersect_key($data, $this->fields) as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], [
                'value' => $value,
                'group' => str_starts_with($key, 'hero') ? 'hero'
                    : (str_starts_with($key, 'seo') ? 'seo' : 'general'),
                'type' => $this->fields[$key][1],
            ]);
        }

        if ($request->hasFile('seo_image')) {
            $file = $request->file('seo_image');
            $previous = SiteSetting::where('key', 'seo_image_path')->value('value');
            if ($previous && str_starts_with($previous, '/storage/')) {
                Storage::disk('public')->delete(substr($previous, 9));
            }
            $path = $file->storePubliclyAs(
                'site', 'seo-share.'.$file->extension(), 'public'
            );
            SiteSetting::updateOrCreate(['key' => 'seo_image_path'], [
                'value' => '/storage/'.$path,
                'group' => 'seo',
                'type' => 'image',
            ]);
        }

        return back()->with('success', 'Contenido del sitio actualizado.');
    }
}
