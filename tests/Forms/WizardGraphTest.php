<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Forms;

use Entelechy\Architect\Forms\Exceptions\WizardGraphException;
use Entelechy\Architect\Forms\WizardGraph;
use Entelechy\Architect\Tests\TestCase;

class WizardGraphTest extends TestCase
{
    public function test_linear_default_next_step(): void
    {
        $graph = new WizardGraph(['a', 'b', 'c'], [], []);

        $this->assertSame('b', $graph->nextStepId('a', []));
        $this->assertSame('c', $graph->nextStepId('b', []));
        $this->assertNull($graph->nextStepId('c', []));
    }

    public function test_branch_resolves_by_field_value(): void
    {
        $graph = new WizardGraph(
            ['type', 'individual', 'company', 'summary'],
            ['type' => ['field' => 'type', 'map' => ['individual' => 'individual', 'company' => 'company']]],
            ['individual' => 'summary', 'company' => 'summary'],
        );

        $graph->validate();

        $this->assertSame('individual', $graph->nextStepId('type', ['type' => 'individual']));
        $this->assertSame('company', $graph->nextStepId('type', ['type' => 'company']));
        $this->assertSame('summary', $graph->nextStepId('individual', []));
        $this->assertSame('summary', $graph->nextStepId('company', []));
    }

    public function test_branch_with_unanswered_field_returns_null(): void
    {
        $graph = new WizardGraph(
            ['type', 'individual', 'company'],
            ['type' => ['field' => 'type', 'map' => ['individual' => 'individual', 'company' => 'company']]],
            [],
        );

        $this->assertNull($graph->nextStepId('type', []));
    }

    public function test_duplicate_step_ids_fail_validation(): void
    {
        $graph = new WizardGraph(['a', 'a'], [], []);

        $this->expectException(WizardGraphException::class);
        $this->expectExceptionMessage("Duplicate wizard step id 'a'");

        $graph->validate();
    }

    public function test_branch_referencing_unknown_from_step_fails(): void
    {
        $graph = new WizardGraph(['a', 'b'], ['x' => ['field' => 'x', 'map' => ['1' => 'b']]], []);

        $this->expectException(WizardGraphException::class);
        $this->expectExceptionMessage("branch() references unknown step id 'x'");

        $graph->validate();
    }

    public function test_branch_targeting_unknown_step_fails(): void
    {
        $graph = new WizardGraph(['a', 'b'], ['a' => ['field' => 'a', 'map' => ['1' => 'ghost']]], []);

        $this->expectException(WizardGraphException::class);
        $this->expectExceptionMessage("targets unknown step id 'ghost'");

        $graph->validate();
    }

    public function test_then_targeting_unknown_step_fails(): void
    {
        $graph = new WizardGraph(['a', 'b'], [], ['a' => 'ghost']);

        $this->expectException(WizardGraphException::class);
        $this->expectExceptionMessage("then() targets unknown step id 'ghost'");

        $graph->validate();
    }

    public function test_unreachable_step_fails(): void
    {
        // 'b' is skipped entirely: 'a' branches directly to 'c', bypassing
        // 'b', and nothing else transitions into 'b'.
        $graph = new WizardGraph(
            ['a', 'b', 'c'],
            ['a' => ['field' => 'a', 'map' => ['x' => 'c']]],
            [],
        );

        $this->expectException(WizardGraphException::class);
        $this->expectExceptionMessage("Wizard step 'b' is unreachable");

        $graph->validate();
    }

    public function test_valid_linear_graph_passes_validation(): void
    {
        $graph = new WizardGraph(['a', 'b', 'c'], [], []);

        $graph->validate();

        $this->addToAssertionCount(1);
    }
}
