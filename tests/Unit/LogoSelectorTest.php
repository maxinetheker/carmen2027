<?php

namespace Tests\Unit;

use App\Services\Ai\AiSettings;
use App\Services\Ai\OpenAiClient;
use App\Services\Brochure\LogoSelector;
use App\Services\Brochure\VisionImageEncoder;
use Tests\TestCase;

class LogoSelectorTest extends TestCase
{
    private function selector(): LogoSelector
    {
        $settings = $this->app->make(AiSettings::class);

        return new LogoSelector(new OpenAiClient($settings), new VisionImageEncoder, $settings);
    }

    public function test_off_mode_returns_no_key_without_calling_the_ai(): void
    {
        $result = $this->selector()->select(['logo_mode' => 'off'], $this->theme());

        $this->assertNull($result['key']);
        $this->assertSame(0, $result['usage']['input_tokens']);
    }

    public function test_manual_mode_returns_the_chosen_key_without_calling_the_ai(): void
    {
        $result = $this->selector()->select(
            ['logo_mode' => 'manual', 'logo_key' => 'vertical_silver'], $this->theme()
        );

        $this->assertSame('vertical_silver', $result['key']);
    }

    public function test_manual_mode_falls_back_to_the_default_for_an_unknown_key(): void
    {
        $result = $this->selector()->select(
            ['logo_mode' => 'manual', 'logo_key' => 'no-existe'], $this->theme()
        );

        $this->assertSame(config('brochure_templates.default_logo'), $result['key']);
    }

    private function theme(): array
    {
        return config('brochure_templates.templates.plantilla-1');
    }
}
