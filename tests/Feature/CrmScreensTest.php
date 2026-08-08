<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\ContactLog;
use App\Models\Lead;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmScreensTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_public_home_shows_certifications_and_editable_stats(): void
    {
        $this->seed();
        SiteSetting::updateOrCreate(['key' => 'stats_years'], ['value' => '11']);
        SiteSetting::updateOrCreate(['key' => 'stats_clients'], ['value' => '250']);
        SiteSetting::updateOrCreate(['key' => 'certifications_title'], ['value' => 'Mis certificaciones']);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('Mis certificaciones')
            ->assertSee('Certified Residential Specialist')
            ->assertSee('Luxury Homes Certification')
            ->assertSee('Industrial Specialist')
            ->assertSee('Commercial Certification')
            ->assertSee('11+', false)
            ->assertSee('Años acompañando decisiones')
            // La firma del hero va fuera del bloque que se encoge.
            ->assertSee('hero-headline', false)
            ->assertSee('Hablas directamente con Carmen');
    }

    public function test_leads_and_contacts_explain_the_difference_and_show_the_log(): void
    {
        $this->seed();
        $user = User::firstOrFail();
        $contact = Contact::create(['first_name' => 'Magaly', 'last_name' => 'Ponce',
            'phone' => '900111222', 'party_type' => 'seller']);
        $contact->contactLogs()->create(['channel' => 'call', 'direction' => 'outgoing',
            'outcome' => 'answered', 'contacted_at' => now()->subDay(), 'notes' => 'Quedó en enviar planos']);

        $this->actingAs($user)->get(route('admin.leads.index'))
            ->assertOk()->assertSee('<strong>prospecto</strong> es alguien que recién llegó', false);
        $this->actingAs($user)->get(route('admin.contacts.index'))
            ->assertOk()->assertSee('<strong>contacto</strong> es tu cartera', false)->assertSee('Vendedor');

        $this->actingAs($user)->get(route('admin.contacts.edit', $contact))
            ->assertOk()
            ->assertSee('Registro de contacto')
            ->assertSee('Quedó en enviar planos')
            ->assertSee('¿De qué lado está?', false);
    }

    public function test_registering_a_call_moves_the_last_contact_date(): void
    {
        $this->seed();
        $lead = Lead::create(['first_name' => 'Fany', 'phone' => '900333444', 'status' => 'new']);

        $this->actingAs(User::firstOrFail())
            ->post(route('admin.leads.logs.store', $lead), [
                'channel' => 'whatsapp', 'direction' => 'outgoing', 'outcome' => 'answered',
                'contacted_at' => now()->format('Y-m-d\TH:i'),
                'next_contact_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
                'notes' => 'Le mandé las fotos del dúplex',
            ])->assertSessionHas('success');

        $lead->refresh();
        $this->assertNotNull($lead->last_contact_at);
        $this->assertNotNull($lead->next_contact_at);
        $this->assertSame(1, ContactLog::count());
    }

    public function test_task_and_agenda_screens_explain_what_goes_where(): void
    {
        $this->seed();
        $user = User::firstOrFail();

        $this->actingAs($user)->get(route('admin.tasks.create'))
            ->assertOk()->assertSee('Avisarme de esta tarea')
            ->assertSee('Fecha límite (opcional)', false);
        $this->actingAs($user)->get(route('admin.appointments.create'))
            ->assertOk()->assertSee('Avisarme de esta cita')
            ->assertSee('Termina (opcional)', false);
    }

    public function test_a_task_can_be_saved_without_a_due_date(): void
    {
        $this->seed();

        $this->actingAs(User::firstOrFail())->post(route('admin.tasks.store'), [
            'title' => 'Pendiente sin fecha', 'priority' => 'medium', 'status' => 'pending',
            'due_at' => '', 'notify_enabled' => '1', 'notify_lead_minutes' => '',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('task_items', ['title' => 'Pendiente sin fecha', 'due_at' => null]);
    }

    public function test_local_comercial_is_offered_everywhere_a_type_is_chosen(): void
    {
        $this->seed();

        $this->get(route('home'))->assertOk()->assertSee('Local comercial');
        $this->get(route('properties.index'))->assertOk()->assertSee('Local comercial');
        $this->actingAs(User::firstOrFail())->get(route('admin.properties.create'))
            ->assertOk()->assertSee('Local comercial');
    }

    public function test_a_property_can_be_saved_and_filtered_as_local(): void
    {
        $this->seed();

        $this->actingAs(User::firstOrFail())->post(route('admin.properties.store'), [
            'title' => 'Local en La Victoria', 'district' => 'La Victoria', 'type' => 'local',
            'operation' => 'venta', 'status' => 'available', 'price' => 250000,
            'currency' => 'USD', 'area' => 200, 'bedrooms' => 0, 'bathrooms' => 2,
            'priority' => 0, 'is_published' => '1',
        ])->assertSessionHasNoErrors();

        $property = \App\Models\Property::where('type', 'local')->firstOrFail();
        $this->assertSame('Local comercial', $property->type_label);
        $this->get(route('properties.index', ['type' => 'local']))
            ->assertOk()->assertSee('Local en La Victoria');
    }

    public function test_the_properties_index_offers_the_url_importer(): void
    {
        $this->seed();

        $this->actingAs(User::firstOrFail())->get(route('admin.properties.index'))
            ->assertOk()
            ->assertSee('Importar desde enlace')
            ->assertSee('Cargar archivo HTML')
            ->assertSee('data-import-html-file', false)
            ->assertSee('data-import-dialog', false);
    }
}
