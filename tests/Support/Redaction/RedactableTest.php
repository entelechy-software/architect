<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Support\Redaction;

use Entelechy\Architect\Forms\Fields\TextField;
use Entelechy\Architect\Support\Redaction\RedactionStrategy;
use Entelechy\Architect\Table\Column;
use Entelechy\Architect\Tests\TestCase;

class RedactableTest extends TestCase
{
    public function test_column_is_not_redacted_by_default(): void
    {
        $this->assertFalse(Column::make('ssn')->isRedacted());
    }

    public function test_column_redact_marks_column_redacted_with_partial_default(): void
    {
        $column = Column::make('ssn')->redact();

        $this->assertTrue($column->isRedacted());
        $this->assertSame('••••6789', $column->applyRedaction('123-45-6789'));
    }

    public function test_column_redact_accepts_named_preset(): void
    {
        $column = Column::make('ssn')->redact('full');

        $this->assertSame('••••••••', $column->applyRedaction('123-45-6789'));
    }

    public function test_column_redact_accepts_explicit_strategy_instance(): void
    {
        $column = Column::make('card')->redact(RedactionStrategy::partial(show: 4, side: 'start'));

        $this->assertSame('4111••••', $column->applyRedaction('4111111111116789'));
    }

    public function test_column_redact_unless_stores_bypass_permission(): void
    {
        $column = Column::make('ssn')->redact()->redactUnless('users.ssn.view');

        $this->assertSame('users.ssn.view', $column->getRedactUnlessPermission());
    }

    public function test_column_revealable_defaults_to_redact_unless_permission(): void
    {
        $column = Column::make('ssn')->redact()->redactUnless('users.ssn.view')->revealable();

        $this->assertTrue($column->isRevealable());
        $this->assertSame('users.ssn.view', $column->getRevealPermission());
    }

    public function test_column_revealable_accepts_its_own_permission_node(): void
    {
        $column = Column::make('ssn')
            ->redact()
            ->redactUnless('users.ssn.view')
            ->revealable('users.ssn.reveal-once');

        $this->assertSame('users.ssn.reveal-once', $column->getRevealPermission());
    }

    public function test_column_redact_is_immutable_clone(): void
    {
        $original = Column::make('ssn');
        $redacted = $original->redact();

        $this->assertFalse($original->isRedacted());
        $this->assertTrue($redacted->isRedacted());
    }

    public function test_forms_field_supports_the_same_redact_api(): void
    {
        $field = TextField::make('ssn')->redact()->redactUnless('users.ssn.view');

        $this->assertTrue($field->isRedacted());
        $this->assertSame('users.ssn.view', $field->getRedactUnlessPermission());
        $this->assertSame('••••6789', $field->applyRedaction('123-45-6789'));
    }
}
