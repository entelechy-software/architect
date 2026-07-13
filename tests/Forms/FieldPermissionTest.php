<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Forms;

use Entelechy\Architect\Forms\Fields\TextField;
use Entelechy\Architect\Tests\TestCase;

class FieldPermissionTest extends TestCase
{
    public function test_no_permission_by_default(): void
    {
        $this->assertNull(TextField::make('salary')->getPermission());
    }

    public function test_permission_sets_node(): void
    {
        $field = TextField::make('salary')->permission('users.salary.view');

        $this->assertSame('users.salary.view', $field->getPermission());
    }

    public function test_permission_returns_new_clone(): void
    {
        $original = TextField::make('salary');
        $withPermission = $original->permission('users.salary.view');

        $this->assertNull($original->getPermission());
        $this->assertSame('users.salary.view', $withPermission->getPermission());
    }
}
