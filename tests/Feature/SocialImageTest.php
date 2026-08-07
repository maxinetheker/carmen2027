<?php

namespace Tests\Feature;

use App\Jobs\GeneratePropertySocialImageJob;
use App\Models\Property;
use App\Models\User;
use App\Services\Ai\OpenAiImageClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SocialImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_modal_offers_the_three_formats_and_two_qualities(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('admin.properties.social.panel', $this->property()));

        $response->assertOk()
            ->assertSee('Generar imagen para redes sociales')
            ->assertSee('Cuadrada')->assertSee('Vertical')->assertSee('Horizontal')
            ->assertSee('Media (recomendada)')->assertSee('Baja (más rápida y económica)')
            ->assertSee('Incluir a Carmen en la pieza');
    }

    public function test_the_modal_drops_the_brochure_only_options(): void
    {
        // Explicitly asked for: no template picker, no page limit, no free-form prompt.
        $response = $this->actingAs(User::factory()->create())
            ->get(route('admin.properties.social.panel', $this->property()));

        $response->assertDontSee('Plantilla')
            ->assertDontSee('Límite máximo de hojas')
            ->assertDontSee('Instrucciones adicionales')
            ->assertDontSee('name="template_key"', false)
            ->assertDontSee('name="max_pages"', false)
            ->assertDontSee('name="extra_prompt"', false);
    }

    public function test_the_modal_keeps_the_options_that_were_asked_for(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('admin.properties.social.panel', $this->property(['latitude' => -12.04, 'longitude' => -77.04])));

        $response->assertSee('name="logo_mode"', false)
            ->assertSee('name="images_mode"', false)
            ->assertSee('name="interest_mode"', false)
            ->assertSee('name="title_mode"', false)
            ->assertSee('name="croquis_mode"', false)
            ->assertSee('name="audience"', false);
    }

    public function test_generating_queues_a_job_and_records_the_history_row(): void
    {
        Queue::fake();
        $property = $this->property();

        $this->actingAs(User::factory()->create())
            ->postJson(route('admin.properties.social.store', $property), $this->postOptions())
            ->assertOk()
            ->assertJsonPath('status', 'queued');

        Queue::assertPushed(GeneratePropertySocialImageJob::class);
        $image = $property->socialImages()->firstOrFail();
        $this->assertSame('vertical', $image->format);
        $this->assertSame('baja', $image->quality);
        $this->assertTrue($image->options['include_agent']);
        $this->assertSame('de pie, sonriendo', $image->options['agent_pose']);
    }

    public function test_history_survives_and_is_listed_newest_first(): void
    {
        Queue::fake();
        $property = $this->property();
        $user = User::factory()->create();

        foreach (['cuadrado', 'vertical'] as $format) {
            $this->actingAs($user)->postJson(
                route('admin.properties.social.store', $property),
                ['format' => $format] + $this->postOptions()
            )->assertOk();
        }

        $this->assertCount(2, $property->socialImages()->get());
        $this->assertSame('vertical', $property->socialImages()->first()->format);
        $this->actingAs($user)->get(route('admin.properties.social.panel', $property))
            ->assertOk()->assertSee('Vertical 2:3')->assertSee('Cuadrada 1:1');
    }

    public function test_an_unknown_format_or_quality_is_rejected(): void
    {
        Queue::fake();

        $this->actingAs(User::factory()->create())->postJson(
            route('admin.properties.social.store', $this->property()),
            ['format' => 'panoramica', 'quality' => 'ultra'] + $this->postOptions()
        )->assertStatus(422)->assertJsonValidationErrors(['format', 'quality']);

        Queue::assertNothingPushed();
    }

    public function test_the_croquis_needs_a_location_here_too(): void
    {
        Queue::fake();

        $this->actingAs(User::factory()->create())->postJson(
            route('admin.properties.social.store', $this->property()),
            ['croquis_mode' => 'auto'] + $this->postOptions()
        )->assertStatus(422)->assertJsonValidationErrors('croquis_mode');

        Queue::assertNothingPushed();
    }

    public function test_formats_map_to_the_sizes_the_openai_api_accepts(): void
    {
        $this->assertSame('1024x1024', OpenAiImageClient::SIZES['cuadrado']);
        $this->assertSame('1024x1536', OpenAiImageClient::SIZES['vertical']);
        $this->assertSame('1536x1024', OpenAiImageClient::SIZES['horizontal']);
        $this->assertSame(['media' => 'medium', 'baja' => 'low'], OpenAiImageClient::QUALITIES);
    }

    private function postOptions(array $overrides = []): array
    {
        return $overrides + [
            'format' => 'vertical', 'quality' => 'baja', 'logo_mode' => 'auto',
            'images_mode' => 'auto', 'interest_mode' => 'off', 'title_mode' => 'off',
            'audience' => 'personas', 'croquis_mode' => 'off',
            'include_agent' => '1', 'agent_pose' => 'de pie, sonriendo',
        ];
    }

    private function property(array $overrides = []): Property
    {
        return Property::create($overrides + [
            'title' => 'Casa de prueba', 'code' => 'CM-SOCIAL', 'slug' => 'casa-social',
            'district' => 'Miraflores', 'type' => 'casa', 'operation' => 'venta',
            'status' => 'available', 'price' => 250000, 'currency' => 'USD', 'area' => 110,
            'bedrooms' => 3, 'bathrooms' => 2, 'priority' => 50,
        ]);
    }
}
