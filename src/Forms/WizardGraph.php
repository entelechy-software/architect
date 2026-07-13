<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms;

use Entelechy\Architect\Forms\Exceptions\WizardGraphException;

/**
 * Resolves "what step comes next" for a wizard, and validates that
 * resolution is sound before the wizard ever reaches runtime.
 *
 * Three transition mechanisms, in priority order for a given step id:
 *
 * 1. A branch() rule for that step id — the next step is looked up from
 *    the branch's map using the current value of its condition field.
 * 2. A then() override for that step id — an explicit fixed next step,
 *    typically used to converge multiple branch destinations back onto a
 *    shared step (e.g. a summary step).
 * 3. The default: the next step in declaration order.
 *
 * Constructed and validated exclusively by WizardBuilder::build() — never
 * constructed directly by host-app code.
 */
final class WizardGraph
{
    /**
     * @param  list<string>  $stepIds  Ordered step ids, as declared.
     * @param  array<string, array{field: string, map: array<int|string, string>}>  $branches  Keyed by the step id the branch decision happens at.
     * @param  array<string, string>  $nextOverrides  Keyed by step id -> the step id that follows it, set via then().
     */
    public function __construct(
        private readonly array $stepIds,
        private readonly array $branches,
        private readonly array $nextOverrides,
    ) {}

    /**
     * @param  array<string, mixed>  $formData
     */
    public function nextStepId(string $currentStepId, array $formData): ?string
    {
        if (isset($this->branches[$currentStepId])) {
            $branch = $this->branches[$currentStepId];
            $value = data_get($formData, $branch['field']);

            if ($value !== null && array_key_exists($value, $branch['map'])) {
                return $branch['map'][$value];
            }

            // The deciding field hasn't been given a mapped value yet —
            // navigation cannot proceed past this step.
            return null;
        }

        if (isset($this->nextOverrides[$currentStepId])) {
            return $this->nextOverrides[$currentStepId];
        }

        $index = array_search($currentStepId, $this->stepIds, true);

        if ($index === false) {
            return null;
        }

        return $this->stepIds[$index + 1] ?? null;
    }

    /**
     * @throws WizardGraphException
     */
    public function validate(): void
    {
        $seen = [];

        foreach ($this->stepIds as $id) {
            if (isset($seen[$id])) {
                throw new WizardGraphException("Duplicate wizard step id '{$id}'. Step ids must be unique within a wizard.");
            }

            $seen[$id] = true;
        }

        foreach ($this->branches as $fromId => $branch) {
            if (! in_array($fromId, $this->stepIds, true)) {
                throw new WizardGraphException("branch() references unknown step id '{$fromId}'.");
            }

            foreach ($branch['map'] as $value => $targetId) {
                if (! in_array($targetId, $this->stepIds, true)) {
                    throw new WizardGraphException(
                        "branch() from step '{$fromId}' targets unknown step id '{$targetId}' for value '{$value}'."
                    );
                }
            }
        }

        foreach ($this->nextOverrides as $fromId => $targetId) {
            if (! in_array($fromId, $this->stepIds, true)) {
                throw new WizardGraphException("then() applies to unknown step id '{$fromId}'.");
            }

            if (! in_array($targetId, $this->stepIds, true)) {
                throw new WizardGraphException("then() targets unknown step id '{$targetId}'.");
            }
        }

        $this->assertReachable();
    }

    /**
     * Breadth-first reachability check from the first declared step,
     * considering every possible branch destination (not just the value
     * that happens to be in formData at validation time — there is no
     * formData at build() time, this must hold for all possible values).
     *
     * @throws WizardGraphException
     */
    private function assertReachable(): void
    {
        if ($this->stepIds === []) {
            return;
        }

        $reached = [$this->stepIds[0] => true];
        $queue = [$this->stepIds[0]];

        while ($queue !== []) {
            $current = array_shift($queue);

            foreach ($this->possibleNextIds($current) as $next) {
                if (! isset($reached[$next])) {
                    $reached[$next] = true;
                    $queue[] = $next;
                }
            }
        }

        foreach ($this->stepIds as $id) {
            if (! isset($reached[$id])) {
                throw new WizardGraphException("Wizard step '{$id}' is unreachable from the first step.");
            }
        }
    }

    /** @return list<string> Every step id reachable as *a* next step from $currentId, across all possible branch values. */
    private function possibleNextIds(string $currentId): array
    {
        if (isset($this->branches[$currentId])) {
            return array_values(array_unique($this->branches[$currentId]['map']));
        }

        if (isset($this->nextOverrides[$currentId])) {
            return [$this->nextOverrides[$currentId]];
        }

        $index = array_search($currentId, $this->stepIds, true);

        if ($index === false) {
            return [];
        }

        $next = $this->stepIds[$index + 1] ?? null;

        return $next !== null ? [$next] : [];
    }
}
