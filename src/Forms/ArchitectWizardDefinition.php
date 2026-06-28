<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms;

use Closure;
use Entelechy\Architect\Forms\Contracts\StructureItem;

/**
 * Frozen value object produced by WizardBuilder::build().
 *
 * Consumed by Forms\Livewire\WizardEngine — never constructed directly by
 * host-app code.
 */
final class ArchitectWizardDefinition
{
    /**
     * @param  string  $key  Stable identifier.
     * @param  array<int, array{label: string, structure: array<int, StructureItem>}>  $steps  Ordered step definitions.
     * @param  Closure|null  $saveUsing  Called on final step submission.
     * @param  string|null  $cancelRoute  Named route to redirect on cancel.
     * @param  string|null  $completedRoute  Named route to redirect after completion.
     */
    public function __construct(
        public readonly string $key,
        public readonly array $steps,
        public readonly ?Closure $saveUsing,
        public readonly ?string $cancelRoute,
        public readonly ?string $completedRoute,
    ) {}

    public function totalSteps(): int
    {
        return count($this->steps);
    }
}
