<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelationalSelectTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_relational_select_uses_the_searchable_component(): void
    {
        $this->seed();
        $this->actingAs(User::firstOrFail());

        $forms = [
            'admin.leads.create' => 1,
            'admin.deals.create' => 4,
            'admin.tasks.create' => 2,
            'admin.appointments.create' => 4,
        ];

        foreach ($forms as $route => $expected) {
            $response = $this->get(route($route))->assertOk();
            $this->assertSame(
                $expected,
                substr_count($response->getContent(), 'data-searchable-select'),
                "Cantidad incorrecta de relaciones buscables en {$route}."
            );
        }
    }
}
