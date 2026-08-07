<?php

namespace Tests\Unit;

use App\Services\Ai\OpenAiImageClient;
use PHPUnit\Framework\TestCase;

/**
 * OpenAI's raw wording ("Project proj_xxx does not have access to model gpt-image-2")
 * tells whoever runs the CRM nothing about where to click, so the client translates the
 * cases that actually occur into steps.
 */
class OpenAiImageErrorTest extends TestCase
{
    public function test_a_model_access_error_names_the_project_and_both_things_to_check(): void
    {
        $explained = $this->explain(
            'Project `proj_wyw2fEdFwSQoN54Ahp547e9m` does not have access to model `gpt-image-2`'
        );

        $this->assertStringContainsString('proj_wyw2fEdFwSQoN54Ahp547e9m', $explained);
        $this->assertStringContainsString('modelos permitidos', $explained);
        $this->assertStringContainsString('Verify Organization', $explained);
    }

    public function test_a_verification_error_points_straight_at_the_setting(): void
    {
        $this->assertStringContainsString(
            'Verify Organization',
            $this->explain('Your organization must be verified to use the model')
        );
    }

    public function test_a_billing_error_says_so_plainly(): void
    {
        $this->assertStringContainsString(
            'no tiene saldo',
            $this->explain('You exceeded your current quota, please check your billing details')
        );
    }

    public function test_an_unrecognised_error_is_passed_through_intact(): void
    {
        $explained = $this->explain('Something entirely new went wrong');

        $this->assertStringContainsString('Something entirely new went wrong', $explained);
        $this->assertStringContainsString('gpt-image-2', $explained);
    }

    private function explain(string $message): string
    {
        $method = new \ReflectionMethod(OpenAiImageClient::class, 'explain');

        return $method->invoke(
            (new \ReflectionClass(OpenAiImageClient::class))->newInstanceWithoutConstructor(),
            $message,
            'gpt-image-2'
        );
    }
}
