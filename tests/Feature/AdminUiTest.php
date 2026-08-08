<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cómo se reparte el espacio en el panel: la explicación de cada sección vive
 * detrás de un botón «i» y los bloques largos (la bitácora de contacto) se abren
 * en un modal, en vez de empujar la ficha hacia abajo.
 */
class AdminUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_section_explanation_lives_behind_the_info_button(): void
    {
        $this->seed();

        $this->actingAs(User::firstOrFail())->get(route('admin.leads.index'))
            ->assertOk()
            ->assertSee('data-info-open', false)
            // El texto sigue estando, pero dentro del modal en vez de una franja
            // fija que se comía el alto útil de la pantalla en cada visita.
            ->assertSee('<dialog class="info-modal" data-info-dialog>', false)
            ->assertSee('<strong>prospecto</strong> es alguien que recién llegó', false)
            ->assertDontSee('class="section-intro"', false);
    }

    public function test_screens_without_an_explanation_show_no_info_button(): void
    {
        $this->seed();

        $this->actingAs(User::firstOrFail())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('data-info-open', false);
    }

    public function test_the_contact_log_opens_as_a_modal_from_the_form_heading(): void
    {
        $this->seed();
        $lead = $this->lead('Zoila', '900555666');
        $lead->contactLogs()->create(['channel' => 'call', 'direction' => 'outgoing',
            'contacted_at' => now()->subHour()]);

        $this->actingAs(User::firstOrFail())->get(route('admin.leads.edit', $lead))
            ->assertOk()
            // El botón que lo abre va arriba, junto al número del registro.
            ->assertSee('data-panel-open="contact-log"', false)
            ->assertSee('Registro de contacto (1)')
            ->assertSee('data-panel-dialog="contact-log"', false)
            ->assertDontSee('data-panel-autoopen', false);
    }

    public function test_a_new_record_has_no_contact_log_yet(): void
    {
        $this->seed();

        $this->actingAs(User::firstOrFail())->get(route('admin.leads.create'))
            ->assertOk()
            ->assertDontSee('data-panel-open', false);
    }

    public function test_saving_a_contact_log_asks_to_reopen_its_modal(): void
    {
        $this->seed();
        $lead = $this->lead('Elba', '900777888');

        $this->actingAs(User::firstOrFail())
            ->post(route('admin.leads.logs.store', $lead), [
                'channel' => 'call', 'direction' => 'outgoing',
                'contacted_at' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHas('openPanel', 'contact-log');

        // El driver de sesión en pruebas es `array` y no sobrevive de una petición
        // a otra, así que la bandera se siembra a mano para ver qué hace la vista.
        $this->actingAs(User::firstOrFail())
            ->withSession(['openPanel' => 'contact-log'])
            ->get(route('admin.leads.edit', $lead))
            ->assertOk()
            ->assertSee('data-panel-autoopen', false);
    }

    public function test_contact_log_errors_stay_inside_the_modal(): void
    {
        $this->seed();
        $lead = $this->lead('Yolanda', '900999000');
        $edit = route('admin.leads.edit', $lead);

        // Son dos <form> distintos en la misma pantalla: un error del registro no
        // debe aparecer como si fuera un error de los datos de la persona.
        $this->actingAs(User::firstOrFail())->from($edit)
            ->post(route('admin.leads.logs.store', $lead), ['channel' => 'humo'])
            ->assertRedirect($edit)
            ->assertSessionHasErrorsIn('contact-log', ['channel', 'direction', 'contacted_at']);

        $this->actingAs(User::firstOrFail())
            ->withSession(['errors' => $this->errorBag('contact-log', 'channel')])
            ->get($edit)
            ->assertOk()
            ->assertSee('data-panel-autoopen', false)
            ->assertSee('Revisa el registro:')
            ->assertDontSee('Revisa la información:');
    }

    private function lead(string $name, string $phone): Lead
    {
        return Lead::create(['first_name' => $name, 'phone' => $phone, 'status' => 'new']);
    }

    /**
     * Las sesiones de este proyecto se serializan como JSON, y en cada arranque
     * `Store::marshalErrorBag()` reconstruye la clave «errors» a partir de este
     * formato. Sembrar un ViewErrorBag ya armado no sirve: lo reemplaza por uno vacío.
     */
    private function errorBag(string $bag, string $field): array
    {
        return [$bag => ['format' => ':message', 'messages' => [$field => ['Elige una opción válida.']]]];
    }
}
