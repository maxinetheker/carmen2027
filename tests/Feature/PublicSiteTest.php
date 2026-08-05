<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\NotificationSetting;
use App\Models\Property;
use App\Notifications\NewLeadReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_and_catalog_are_available(): void
    {
        $this->seed();

        $this->get('/')->assertOk()
            ->assertSee('Carmen Mestanza')
            ->assertSee('/images/carmen-mestanza.png', false)
            ->assertSee('/images/carmen-mestanza-logo.webp', false)
            ->assertSee('/images/remax-logo-light.svg', false)
            ->assertSee('footer-remax-profile', false)
            ->assertSee('cmestanza@remaxintegrity.pe', false)
            ->assertSee('www.remax.pe/web/agents/cmestanza@remaxintegrity.pe/remax-integrity/', false)
            ->assertDontSee('header-remax', false)
            ->assertSee('Tu asesora inmobiliaria de confianza')
            ->assertSee('<strong>6+</strong><span>años de<br>experiencia</span>', false)
            ->assertSee('data-hero-carousel', false)
            ->assertSee('aria-roledescription="carrusel"', false)
            ->assertSee('draggable="false"', false)
            ->assertSee('has-narrow-image', false)
            ->assertSee('hero-slide-backdrop', false)
            ->assertSee('Ático con terraza panorámica');
        $this->get('/propiedades')->assertOk()
            ->assertSee('propiedades encontradas')
            ->assertSee('Buscar cerca de mí')
            ->assertSee('name="min_price"', false)
            ->assertSee('Miraflores (2)');
        $property = Property::first();
        $this->get(route('properties.show', $property))
            ->assertOk()->assertSee($property->title);
    }

    public function test_catalog_can_filter_by_price_zone_and_proximity(): void
    {
        $this->seed();

        $this->get('/propiedades?district=Miraflores&currency=USD&min_price=400000&max_price=500000')
            ->assertOk()->assertSee('Ático con terraza panorámica')
            ->assertDontSee('Residencia contemporánea en parque');
        $this->get('/propiedades?operation=alquiler&latitude=-12.149&longitude=-77.0208&radius=3')
            ->assertOk()->assertSee('Departamento de diseño junto al malecón')
            ->assertDontSee('Oficina premium con vista urbana');
    }

    public function test_property_location_always_uses_an_embedded_google_map(): void
    {
        $this->seed();
        $property = Property::firstOrFail();

        $this->get(route('properties.show', $property))->assertOk()
            ->assertSee('Abrir en Google Maps')
            ->assertSee('maps.google.com/maps', false)
            ->assertDontSee('openstreetmap.org', false);
        config(['services.google_maps.key' => 'testing-key']);
        $this->get(route('properties.show', $property))->assertOk()
            ->assertSee('google.com/maps/embed/v1/place', false)
            ->assertSee('testing-key', false);
    }

    public function test_zero_rooms_are_hidden_and_bathroom_decimals_only_show_when_needed(): void
    {
        $property = Property::create([
            'title' => 'Terreno industrial', 'slug' => 'terreno-industrial',
            'code' => 'TER-001', 'type' => 'terreno', 'operation' => 'venta',
            'district' => 'Chilca', 'price' => 300000, 'currency' => 'USD',
            'bedrooms' => 0, 'bathrooms' => 0, 'area' => 30000,
            'status' => 'available', 'is_published' => true,
        ]);

        $this->get(route('properties.show', $property))->assertOk()
            ->assertDontSee('>Baños<', false)
            ->assertDontSee('>Dormitorios<', false);

        $property->update(['bathrooms' => 1.5]);
        $this->get(route('properties.show', $property))->assertOk()
            ->assertSee('>1.5</strong><small>Baños</small>', false);

        $property->update(['bathrooms' => 2]);
        $this->get(route('properties.show', $property))->assertOk()
            ->assertSee('>2</strong><small>Baños</small>', false)
            ->assertDontSee('>2.0</strong><small>Baños</small>', false);
    }

    public function test_unpublished_property_is_hidden_from_public_pages(): void
    {
        $this->seed();
        $property = Property::firstOrFail();
        $property->update(['is_published' => false]);

        $this->get('/propiedades')->assertOk()->assertDontSee($property->title);
        $this->get(route('properties.show', $property))->assertNotFound();
    }

    public function test_contact_form_creates_a_lead(): void
    {
        Notification::fake();
        NotificationSetting::create([
            'recipient_email' => 'carmen@example.com',
            'recipient_emails' => ['carmen@example.com', 'equipo@example.com'],
        ]);

        $this->post(route('lead.capture'), [
            'first_name' => 'Ana',
            'last_name' => 'Torres',
            'phone' => '999888777',
            'email' => 'ana@example.com',
            'interest' => 'Quiero comprar',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('leads', [
            'phone' => '999888777',
            'source' => 'web',
            'status' => 'new',
        ]);
        $this->assertSame(1, Lead::count());
        Notification::assertSentOnDemandTimes(NewLeadReceived::class, 2);
    }
}
