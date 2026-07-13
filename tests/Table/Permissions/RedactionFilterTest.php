<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Table\Permissions;

use Entelechy\Architect\Contracts\PermissionResolver;
use Entelechy\Architect\Table\Column;
use Entelechy\Architect\Table\Permissions\RedactionFilter;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Contracts\Auth\Authenticatable;

class RedactionFilterTest extends TestCase
{
    public function test_non_redacted_columns_pass_through_untouched(): void
    {
        $filter = new RedactionFilter($this->allowNothing());
        $columns = [Column::make('name')];

        $row = $filter->redactRow(null, $columns, ['name' => 'Ada Lovelace']);

        $this->assertSame('Ada Lovelace', $row['name']);
    }

    public function test_redacted_column_is_masked_when_user_lacks_bypass_permission(): void
    {
        $filter = new RedactionFilter($this->allowNothing());
        $columns = [Column::make('ssn')->redact()->redactUnless('users.ssn.view')];

        $row = $filter->redactRow(null, $columns, ['ssn' => '123-45-6789']);

        $this->assertSame('••••6789', $row['ssn']);
    }

    public function test_redacted_column_shows_real_value_when_user_holds_bypass_permission(): void
    {
        $filter = new RedactionFilter($this->allow('users.ssn.view'));
        $columns = [Column::make('ssn')->redact()->redactUnless('users.ssn.view')];

        $row = $filter->redactRow(null, $columns, ['ssn' => '123-45-6789']);

        $this->assertSame('123-45-6789', $row['ssn']);
    }

    public function test_redacted_column_without_bypass_permission_configured_always_masks(): void
    {
        $filter = new RedactionFilter($this->allowEverything());
        $columns = [Column::make('ssn')->redact()];

        $row = $filter->redactRow(null, $columns, ['ssn' => '123-45-6789']);

        $this->assertSame('••••6789', $row['ssn']);
    }

    public function test_null_values_are_left_untouched(): void
    {
        $filter = new RedactionFilter($this->allowNothing());
        $columns = [Column::make('ssn')->redact()];

        $row = $filter->redactRow(null, $columns, ['ssn' => null]);

        $this->assertNull($row['ssn']);
    }

    public function test_can_reveal_requires_redacted_and_revealable_and_permission(): void
    {
        $filter = new RedactionFilter($this->allow('users.ssn.reveal'));

        $notRedacted = Column::make('ssn')->revealable('users.ssn.reveal');
        $notRevealable = Column::make('ssn')->redact();
        $revealableButUnpermitted = Column::make('ssn')->redact()->revealable('users.ssn.other');
        $fullyEligible = Column::make('ssn')->redact()->revealable('users.ssn.reveal');

        $this->assertFalse($filter->canReveal(null, $notRedacted));
        $this->assertFalse($filter->canReveal(null, $notRevealable));
        $this->assertFalse($filter->canReveal(null, $revealableButUnpermitted));
        $this->assertTrue($filter->canReveal(null, $fullyEligible));
    }

    private function allowNothing(): PermissionResolver
    {
        return new class implements PermissionResolver
        {
            public function can(?Authenticatable $user, string $node): bool
            {
                return false;
            }

            public function canOnRecord(?Authenticatable $user, string $action, mixed $record): bool
            {
                return false;
            }
        };
    }

    private function allowEverything(): PermissionResolver
    {
        return new class implements PermissionResolver
        {
            public function can(?Authenticatable $user, string $node): bool
            {
                return true;
            }

            public function canOnRecord(?Authenticatable $user, string $action, mixed $record): bool
            {
                return true;
            }
        };
    }

    private function allow(string $allowedNode): PermissionResolver
    {
        return new class($allowedNode) implements PermissionResolver
        {
            public function __construct(private string $allowedNode) {}

            public function can(?Authenticatable $user, string $node): bool
            {
                return $node === $this->allowedNode;
            }

            public function canOnRecord(?Authenticatable $user, string $action, mixed $record): bool
            {
                return false;
            }
        };
    }
}
