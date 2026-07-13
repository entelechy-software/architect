<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Forms;

use Entelechy\Architect\Forms\Exceptions\DuplicateFormKeyException;
use Entelechy\Architect\Forms\FormKeyRegistry;
use Entelechy\Architect\Tests\TestCase;

class FormKeyRegistryTest extends TestCase
{
    public function test_registers_a_key(): void
    {
        $registry = new FormKeyRegistry;
        $registry->register('profile-form', 'App\\Forms\\ProfileForm');

        $this->assertSame(['profile-form' => 'App\\Forms\\ProfileForm'], $registry->all());
    }

    public function test_same_class_registering_same_key_twice_is_fine(): void
    {
        $registry = new FormKeyRegistry;
        $registry->register('profile-form', 'App\\Forms\\ProfileForm');
        $registry->register('profile-form', 'App\\Forms\\ProfileForm');

        $this->assertSame(['profile-form' => 'App\\Forms\\ProfileForm'], $registry->all());
    }

    public function test_different_classes_reusing_a_key_throws(): void
    {
        $registry = new FormKeyRegistry;
        $registry->register('settings-form', 'App\\Forms\\ProfileForm');

        $this->expectException(DuplicateFormKeyException::class);
        $this->expectExceptionMessage("Form key 'settings-form' is already registered");

        $registry->register('settings-form', 'App\\Forms\\NotificationsForm');
    }

    public function test_reset_clears_registry(): void
    {
        $registry = new FormKeyRegistry;
        $registry->register('a', 'App\\Forms\\A');
        $registry->reset();

        $this->assertSame([], $registry->all());
    }
}
