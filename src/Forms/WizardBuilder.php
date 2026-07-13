<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms;

use Closure;
use Entelechy\Architect\Forms\Contracts\StructureItem;
use Entelechy\Architect\Forms\Exceptions\WizardGraphException;
use Illuminate\Support\Str;

/**
 * Fluent builder for a multi-step Architect wizard form.
 *
 * Usage:
 *   Architect::wizard('onboarding')
 *       ->step(id: 'personal', label: 'Personal Details', structure: [
 *           TextField::make('name')->required(),
 *           EmailField::make('email')->required(),
 *       ])
 *       ->step(id: 'preferences', label: 'Preferences', structure: [
 *           SelectField::make('role')->options([...]),
 *       ])
 *       ->saveUsing(fn (array $data) => User::create($data))
 *       ->completedRoute('/dashboard')
 *       ->build();
 *
 * Branching:
 *   ->branch(from: 'applicant_type', map: ['individual' => 'individual_details', 'company' => 'company_details'])
 *   ->then('summary')
 *
 * `from` is the step id at which the decision is made. By default the
 * condition is read from a field of the same name as the step id — pass
 * `field:` explicitly when the step id and the deciding field's name
 * differ. `then()` sets the "next step" for whichever step(s) were the
 * destinations of the most recent branch() call, converging them onto a
 * shared step (typically a summary/confirmation step) instead of falling
 * through to their default array-order successor.
 */
final class WizardBuilder
{
    /** @var array<int, array{id: string, label: string, structure: array<int, StructureItem>}> */
    private array $steps = [];

    /** @var array<string, array{field: string, map: array<int|string, string>}> */
    private array $branches = [];

    /** @var array<string, string> */
    private array $nextOverrides = [];

    /** @var list<string> Destination step ids of the most recent branch() call, awaiting a then(). */
    private array $pendingJoinSources = [];

    private ?Closure $saveUsing = null;

    private ?string $cancelRoute = null;

    private ?string $completedRoute = null;

    private bool $draftsEnabled = false;

    private ?string $resumeKey = null;

    private bool $resumeToStepFromDraft = false;

    private bool $guardDirtyNavigation = false;

    private ?Closure $onStepValidated = null;

    private ?string $onSavedDispatchEvent = null;

    /** @var array<string, mixed> */
    private array $onSavedDispatchPayload = [];

    private ?Closure $onSaveSuccess = null;

    private ?Closure $onSaveFailure = null;

    private ?string $supersearchLabel = null;

    private function __construct(private string $key) {}

    public static function make(string $key): static
    {
        return new self($key);
    }

    /**
     * @param  array<int, StructureItem>  $structure
     * @param  string|null  $id  Stable identifier used for branching, deep links, and draft resume. Auto-derived from $label (slugified) when omitted — existing 2-argument call sites are unaffected, but an explicit id is recommended for anything that will be branched to or deep-linked.
     */
    public function step(string $label, array $structure, ?string $id = null): static
    {
        $this->steps[] = [
            'id' => $id ?? Str::slug($label, '_'),
            'label' => $label,
            'structure' => $structure,
        ];

        return $this;
    }

    /**
     * Make the step after $from conditional on one of its fields' values.
     *
     * @param  string  $from  The step id at which the decision is made.
     * @param  array<int|string, string>  $map  Field value => destination step id.
     * @param  string|null  $field  Name of the field whose value decides the branch. Defaults to $from itself.
     */
    public function branch(string $from, array $map, ?string $field = null): static
    {
        $this->branches[$from] = [
            'field' => $field ?? $from,
            'map' => $map,
        ];

        $this->pendingJoinSources = array_values(array_unique($map));

        return $this;
    }

    /**
     * Converge the destination steps of the most recent branch() call onto
     * a single next step (typically a summary/confirmation step).
     */
    public function then(string $stepId): static
    {
        foreach ($this->pendingJoinSources as $source) {
            $this->nextOverrides[$source] = $stepId;
        }

        $this->pendingJoinSources = [];

        return $this;
    }

    /** Persist in-progress wizard state so it can be resumed later. */
    public function drafts(bool $enabled = true): static
    {
        $this->draftsEnabled = $enabled;

        return $this;
    }

    /**
     * A formData key whose value, once present, additionally identifies
     * this wizard's draft across sessions/devices (not just the current
     * browser session).
     */
    public function resumeUsingKey(string $key): static
    {
        $this->resumeKey = $key;

        return $this;
    }

    /** Whether mount() restores the step position (not just field data) from a saved draft. */
    public function resumeToStepFromDraft(bool $enabled = true): static
    {
        $this->resumeToStepFromDraft = $enabled;

        return $this;
    }

    /** Require confirmation before navigating away with unsaved changes. */
    public function guardDirtyNavigation(bool $enabled = true): static
    {
        $this->guardDirtyNavigation = $enabled;

        return $this;
    }

    /** @param  Closure(string, array<string, mixed>): void  $callback  Called with (stepId, formData) after each successful step validation. */
    public function onStepValidated(Closure $callback): static
    {
        $this->onStepValidated = $callback;

        return $this;
    }

    public function saveUsing(Closure $callback): static
    {
        $this->saveUsing = $callback;

        return $this;
    }

    public function cancelRoute(string $route): static
    {
        $this->cancelRoute = $route;

        return $this;
    }

    public function completedRoute(string $route): static
    {
        $this->completedRoute = $route;

        return $this;
    }

    /**
     * Dispatch an additional custom browser event after successful
     * completion, alongside the standard architect:wizard:completed event
     * (FORMS_FEATURE_PLAN.md Phase 5).
     *
     * @param  array<string, mixed>  $payload
     */
    public function onSavedDispatch(string $event, array $payload = []): static
    {
        $this->onSavedDispatchEvent = $event;
        $this->onSavedDispatchPayload = $payload;

        return $this;
    }

    /**
     * Register success/failure callbacks invoked around saveUsing() on
     * final submission, intended to call Architect::toast()/
     * Architect::alert() from the existing Notifications subsystem
     * (FORMS_FEATURE_PLAN.md Architectural Principle #10). The exception
     * is always rethrown after a failure callback runs.
     */
    public function notifyOnSave(?Closure $success = null, ?Closure $failure = null): static
    {
        $this->onSaveSuccess = $success;
        $this->onSaveFailure = $failure;

        return $this;
    }

    /**
     * Declares this wizard as a Supersearch entry point. See
     * FormBuilder::exposeToSupersearch()/FormSearchSet for the full
     * wiring contract — a label alone has nothing to link to until the
     * host app wires FormSearchSet into HasSupersearchHook with a URL.
     */
    public function exposeToSupersearch(string $label): static
    {
        $this->supersearchLabel = $label;

        return $this;
    }

    /**
     * @throws WizardGraphException
     */
    public function build(): ArchitectWizardDefinition
    {
        $stepIds = array_column($this->steps, 'id');

        $graph = new WizardGraph($stepIds, $this->branches, $this->nextOverrides);
        $graph->validate();

        $this->assertBranchFieldsExist();

        return new ArchitectWizardDefinition(
            key: $this->key,
            steps: $this->steps,
            saveUsing: $this->saveUsing,
            cancelRoute: $this->cancelRoute,
            completedRoute: $this->completedRoute,
            graph: $graph,
            draftsEnabled: $this->draftsEnabled,
            resumeKey: $this->resumeKey,
            resumeToStepFromDraft: $this->resumeToStepFromDraft,
            guardDirtyNavigation: $this->guardDirtyNavigation,
            onStepValidated: $this->onStepValidated,
            onSavedDispatchEvent: $this->onSavedDispatchEvent,
            onSavedDispatchPayload: $this->onSavedDispatchPayload,
            onSaveSuccess: $this->onSaveSuccess,
            onSaveFailure: $this->onSaveFailure,
            supersearchLabel: $this->supersearchLabel,
        );
    }

    /**
     * @throws WizardGraphException
     */
    private function assertBranchFieldsExist(): void
    {
        foreach ($this->branches as $fromId => $branch) {
            $step = null;
            foreach ($this->steps as $s) {
                if ($s['id'] === $fromId) {
                    $step = $s;
                    break;
                }
            }

            if ($step === null) {
                // Already reported by WizardGraph::validate(), but guard anyway.
                continue;
            }

            $fieldNames = $this->collectFieldNames($step['structure']);

            if (! in_array($branch['field'], $fieldNames, true)) {
                throw new WizardGraphException(
                    "branch() from step '{$fromId}' reads field '{$branch['field']}', but no field with that name exists in that step. ".
                    'Pass field: explicitly if the deciding field lives elsewhere or is named differently from the step id.'
                );
            }
        }
    }

    /**
     * @param  array<int, StructureItem>  $structure
     * @return list<string>
     */
    private function collectFieldNames(array $structure): array
    {
        $names = [];

        foreach ($structure as $item) {
            if (method_exists($item, 'getName')) {
                $names[] = $item->getName();
            }

            if (method_exists($item, 'getStructure')) {
                $names = array_merge($names, $this->collectFieldNames($item->getStructure()));
            }
        }

        return $names;
    }
}
