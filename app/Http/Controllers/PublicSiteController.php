<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\NotificationSetting;
use App\Models\Property;
use App\Models\SiteSetting;
use App\Notifications\NewLeadReceived;
use App\Services\PropertyCatalogSearch;
use App\Services\PropertySharePreview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class PublicSiteController extends Controller
{
    public function home()
    {
        $selection = Property::with('media')->published()
            ->where('status', 'available')
            ->where(fn ($query) => $query->where('show_in_hero', true)
                ->orWhere('featured', true))
            ->ranked()->take(6)->get();
        $heroProperties = $selection->take(5);
        if ($heroProperties->isEmpty()) {
            $heroProperties = Property::with('media')->published()
                ->where('status', 'available')->ranked()->take(5)->get();
        }

        return view('public.home', [
            'featured' => $selection->isNotEmpty() ? $selection : $heroProperties,
            'heroProperties' => $heroProperties,
            'settings' => SiteSetting::values(),
            'stats' => [
                'properties' => Property::published()->count(),
                'clients' => Lead::where('status', 'won')->count() + 48,
                'years' => 6,
            ],
        ]);
    }

    public function properties(Request $request, PropertyCatalogSearch $catalog)
    {
        return view('public.properties', $catalog->search($request) + [
            'settings' => SiteSetting::values(),
        ]);
    }

    public function property(Property $property)
    {
        abort_unless($property->is_published, 404);
        $property->load(['media', 'features', 'youtubeVideos']);

        return view('public.property', [
            'property' => $property,
            'settings' => SiteSetting::values(),
            'googleMapsKey' => config('services.google_maps.key'),
            'related' => Property::with('media')->published()
                ->where('status', 'available')->where('district', $property->district)
                ->whereKeyNot($property->id)->ranked()->take(3)->get(),
        ]);
    }

    public function propertyShareImage(Property $property, PropertySharePreview $preview)
    {
        abort_unless($property->is_published, 404);

        return response()->file($preview->create($property), [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=86400, stale-while-revalidate=604800',
        ]);
    }

    public function capture(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'interest' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:1500'],
        ]);
        $data['source'] = 'web';
        $data['status'] = 'new';
        $lead = Lead::create($data);
        $this->notifyNewLead($lead);

        return back()->with('success',
            '¡Gracias! Carmen se comunicará contigo muy pronto.');
    }

    private function notifyNewLead(Lead $lead): void
    {
        try {
            $recipients = NotificationSetting::current()->recipients();
        } catch (\Throwable $exception) {
            report($exception);

            return;
        }

        foreach ($recipients as $email) {
            try {
                Notification::route('mail', $email)
                    ->notify(new NewLeadReceived($lead));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
    }
}
