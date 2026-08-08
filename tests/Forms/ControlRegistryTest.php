<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Forms;

use Entelechy\Architect\Facades\Architect;
use Entelechy\Architect\Forms\ControlRegistry;
use Entelechy\Architect\Forms\Fields\CurrencyField;
use Entelechy\Architect\Forms\Fields\TextField;
use Entelechy\Architect\Support\Maturity;
use Entelechy\Architect\Tests\TestCase;

class ControlRegistryTest extends TestCase
{
    public function test_register_and_get(): void
    {
        $registry = new ControlRegistry;
        $registry->register('text', TextField::class, 'Text & Structured Text', 'string', 'Single-line text input.', Maturity::Stable);

        $control = $registry->get('text');

        $this->assertNotNull($control);
        $this->assertSame('text', $control->key());
        $this->assertSame(TextField::class, $control->fieldClass());
        $this->assertSame('Text & Structured Text', $control->category());
        $this->assertSame('string', $control->valueType());
        $this->assertSame(Maturity::Stable, $control->maturity());
    }

    public function test_has_reflects_registration(): void
    {
        $registry = new ControlRegistry;

        $this->assertFalse($registry->has('currency'));

        $registry->register('currency', CurrencyField::class, 'Numeric', 'decimal', 'Currency amount input.', Maturity::Stable);

        $this->assertTrue($registry->has('currency'));
    }

    public function test_by_category_filters(): void
    {
        $registry = new ControlRegistry;
        $registry->register('text', TextField::class, 'Text & Structured Text', 'string', '...', Maturity::Stable);
        $registry->register('currency', CurrencyField::class, 'Numeric', 'decimal', '...', Maturity::Stable);

        $numeric = $registry->byCategory('Numeric');

        $this->assertCount(1, $numeric);
        $this->assertArrayHasKey('currency', $numeric);
    }

    public function test_by_maturity_filters(): void
    {
        $registry = new ControlRegistry;
        $registry->register('text', TextField::class, 'Text & Structured Text', 'string', '...', Maturity::Stable);
        $registry->register('currency', CurrencyField::class, 'Numeric', 'decimal', '...', Maturity::Experimental);

        $experimental = $registry->byMaturity(Maturity::Experimental);

        $this->assertCount(1, $experimental);
        $this->assertArrayHasKey('currency', $experimental);
    }

    public function test_container_singleton_is_seeded_by_service_provider(): void
    {
        $registry = $this->app->make(ControlRegistry::class);

        // Wave A (existing) + Wave B/C (new) should both be present.
        $this->assertTrue($registry->has('text'));
        $this->assertTrue($registry->has('select'));
        $this->assertTrue($registry->has('rich-editor'));
        $this->assertTrue($registry->has('currency'));
        $this->assertTrue($registry->has('percentage'));
        $this->assertTrue($registry->has('date-range'));
        $this->assertTrue($registry->has('phone'));
        $this->assertTrue($registry->has('otp'));
        $this->assertTrue($registry->has('rating'));
    }

    public function test_architect_facade_controls_accessor(): void
    {
        $registry = Architect::controls();

        $this->assertInstanceOf(ControlRegistry::class, $registry);
        $this->assertTrue($registry->has('text'));
    }
}
