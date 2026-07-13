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
     * @param  array<int, array{id: string, label: string, structure: array<int, StructureItem>}>  $steps  Ordered step definitions.
     * @param  Closure|null  $saveUsing  Called on final step submission.
     * @param  string|null  $cancelRoute  Named route to redirect on cancel.
     * @param  string|null  $completedRoute  Named route to redirect after completion.
     * @param  WizardGraph  $graph  Pre-validated step transition graph (branching, joins, linear default).
     * @param  bool  $draftsEnabled  Whether in-progress state is persisted for later resume.
     * @param  string|null  $resumeKey  formData key whose value, once present, additionally identifies this wizard's draft across sessions.
     * @param  bool  $resumeToStepFromDraft  Whether mount() restores the step position (not just field data) from a saved draft.
     * @param  bool  $guardDirtyNavigation  Whether leaving with unsaved changes requires confirmation.
     * @param  Closure|null  $onStepValidated  Called with (string $stepId, array $data) after each successful step validation.
     * @param  array<string, mixed>  $onSavedDispatchPayload
     */
    public function __construct(
        public readonly string $key,
        public readonly array $steps,
        public readonly ?Closure $saveUsing,
        public readonly ?string $cancelRoute,
        public readonly ?string $completedRoute,
        public readonly WizardGraph $graph,
        public readonly bool $draftsEnabled = false,
        public readonly ?string $resumeKey = null,
        public readonly bool $resumeToStepFromDraft = false,
        public readonly bool $guardDirtyNavigation = false,
        public readonly ?Closure $onStepValidated = null,
        public readonly ?string $onSavedDispatchEvent = null,
        public readonly array $onSavedDispatchPayload = [],
        public readonly ?Closure $onSaveSuccess = null,
        public readonly ?Closure $onSaveFailure = null,
        public readonly ?string $supersearchLabel = null,
    ) {}

    public function totalSteps(): int
    {
        return count($this->steps);
    }

    public function firstStepId(): ?string
    {
        return $this->steps[0]['id'] ?? null;
    }

    /** @return array{id: string, label: string, structure: array<int, StructureItem>}|null */
    public function findStep(string $id): ?array
    {
        foreach ($this->steps as $step) {
            if ($step['id'] === $id) {
                return $step;
            }
        }

        return null;
    }

    public function stepIndex(string $id): ?int
    {
        foreach ($this->steps as $index => $step) {
            if ($step['id'] === $id) {
                return $index;
            }
        }

        return null;
    }
}
