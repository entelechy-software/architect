<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Forms;

use Entelechy\Architect\Forms\Validation\NativeValidationPipeline;
use Entelechy\Architect\Tests\TestCase;

class ValidationPipelineTest extends TestCase
{
    public function test_valid_data_returns_no_errors(): void
    {
        $pipeline = new NativeValidationPipeline;

        $errors = $pipeline->validate(['name' => ['required', 'string']], ['name' => 'Greg']);

        $this->assertSame([], $errors);
    }

    public function test_invalid_data_returns_errors_keyed_by_field(): void
    {
        $pipeline = new NativeValidationPipeline;

        $errors = $pipeline->validate(['name' => ['required']], ['name' => '']);

        $this->assertArrayHasKey('name', $errors);
    }
}
