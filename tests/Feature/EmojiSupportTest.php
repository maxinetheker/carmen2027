<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Support\RichTextSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmojiSupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_facebook_emoji_are_preserved_and_prepared_for_safe_rendering(): void
    {
        $emoji = '👩🏽‍💼 ❤️‍🔥 🇵🇪';
        $html = '<p>Asesoría '.$emoji.' <img src="facebook.test/emoji" alt="🏡">'
            .'<img src="facebook.test/photo" alt="Fotografía"></p>';
        $clean = app(RichTextSanitizer::class)->clean($html);

        $this->assertStringContainsString($emoji, $clean);
        $this->assertStringContainsString('🏡', $clean);
        $this->assertStringNotContainsString('<img', $clean);
        $this->assertStringNotContainsString('Fotografía', $clean);

        $property = Property::create([
            'title' => 'Casa Unicode', 'slug' => 'casa-unicode', 'code' => 'EMOJI-1',
            'type' => 'casa', 'operation' => 'venta', 'district' => 'Miraflores',
            'price' => 500000, 'currency' => 'USD', 'bedrooms' => 3,
            'bathrooms' => 2, 'area' => 140, 'status' => 'available',
            'description' => $clean, 'is_published' => true,
        ]);

        $this->assertSame($clean, $property->fresh()->description);
        $this->get(route('properties.show', $property))->assertOk()
            ->assertSee('data-emoji-render', false)
            ->assertSee($emoji);
    }
}
