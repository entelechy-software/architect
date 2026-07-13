<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Livewire;

use Entelechy\Architect\Forms\ArchitectWizardDefinition;
use Entelechy\Architect\Forms\Concerns\FlattensStructure;
use Entelechy\Architect\Forms\Concerns\SanitizesFormData;
use Entelechy\Architect\Forms\Contracts\StructureItem;
use Entelechy\Architect\Forms\Contracts\WizardDraftStore;
use Entelechy\Architect\Forms\Events\EventPayload;
use Entelechy\Architect\Forms\Events\FormEvents;
use Entelechy\Architect\Forms\FormKeyRegistry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Multi-step wizard form engine.
 *
 * Mounted with the FQCN of a host-app class exposing a static
 * `definition(): ArchitectWizardDefinition` method. Blade usage:
 *
 *   <livewire:architect-wizard-engine definition-class="\App\Wizards\OnboardingWizard" />
 *
 * Registration: 'architect-wizard-engine'
 *
 * Navigation is id-based (see ArchitectWizardDefinition/WizardGraph) rather
 * than index-based, so branching, deep links, and draft resume all target
 * stable step ids instead of positions that shift under branching.
 */
class WizardEngine extends Component
{
    use FlattensStructure;
    use SanitizesFormData;

    /** @var class-string */
    public string $definitionClass;

    /**
     * Reflected in the URL as ?step=... (except when it equals the empty
     * string, i.e. before mount() sets an initial value) so a specific
     * wizard step can be deep-linked to and survives a page reload
     * (FORMS_FEATURE_PLAN.md Phase 5, "Deep links to forms and wizard
     * steps"). Livewire hydrates #[Url] properties before mount() runs,
     * so mount() below honors an incoming URL value when it names a real
     * step, falling back to the definition's first step otherwise.
     */
    #[Url(as: 'step', except: '')]
    public string $currentStepId = '';

    /** @var list<string> Visited step ids, in order — used by previousStep() since branching means "previous" is not simply "index - 1". */
    public array $history = [];

    /** @var array<string, mixed> */
    public array $formData = [];

    /**
     * Snapshot of formData taken at mount() and refreshed after every
     * successful draft save. Used both by SanitizesFormData (anti-tamper
     * revert target) and by isDirty() (dirty-navigation guard).
     *
     * @var array<string, mixed>
     */
    protected array $originalFormData = [];

    public bool $completed = false;

    /** @param  class-string  $definitionClass */
    public function mount(string $definitionClass): void
    {
        $this->definitionClass = $definitionClass;
        $definition = $this->resolveDefinition();

        app(FormKeyRegistry::class)->register($definition->key, $definitionClass);

        // Pre-fill all fields from all steps with their defaults.
        foreach ($definition->steps as $step) {
            foreach ($this->flattenFields($step['structure']) as $field) {
                $this->formData[$field->getName()] = $field->getDefault();
            }
        }

        // Honor a step id already hydrated from the URL (deep link) if it
        // names a real step; otherwise start from the beginning as before.
        if ($this->currentStepId === '' || $definition->findStep($this->currentStepId) === null) {
            $this->currentStepId = (string) $definition->firstStepId();
        }

        $this->history = $this->currentStepId !== '' ? [$this->currentStepId] : [];

        if ($definition->draftsEnabled) {
            $draft = $this->loadDraft($definition);

            if ($draft !== null) {
                /** @var array<string, mixed> $draftFormData */
                $draftFormData = $draft['form_data'] ?? [];
                $this->formData = array_merge($this->formData, $draftFormData);

                $draftStepId = $draft['current_step_id'] ?? null;

                if (
                    $definition->resumeToStepFromDraft
                    && is_string($draftStepId)
                    && $definition->findStep($draftStepId) !== null
                ) {
                    $this->currentStepId = $draftStepId;
                    /** @var list<string> $draftHistory */
                    $draftHistory = $draft['history'] ?? [$draftStepId];
                    $this->history = $draftHistory;
                }
            }
        }

        $this->originalFormData = $this->formData;

        $this->dispatch(
            FormEvents::WIZARD_STEP_ENTERED,
            ...EventPayload::make($definition->key, ['step_id' => $this->currentStepId])
        );
    }

    public function nextStep(): void
    {
        $definition = $this->resolveDefinition();
        $step = $definition->findStep($this->currentStepId);

        if ($step === null) {
            return;
        }

        $this->formData = $this->sanitizeAgainstFields($step['structure'], $this->formData, $this->originalFormData);

        $this->validate($this->rulesForStep($step));

        if ($definition->onStepValidated !== null) {
            ($definition->onStepValidated)($this->currentStepId, $this->formData);
        }

        $this->dispatch(
            FormEvents::WIZARD_STEP_VALIDATED,
            ...EventPayload::make($definition->key, ['step_id' => $this->currentStepId])
        );

        $nextId = $definition->graph->nextStepId($this->currentStepId, $this->formData);

        if ($nextId === null) {
            // Either already the last step, or a branch decision field has
            // no mapped value yet — nothing to navigate to.
            return;
        }

        $this->dispatch(
            FormEvents::WIZARD_STEP_LEAVING,
            ...EventPayload::make($definition->key, ['step_id' => $this->currentStepId])
        );

        $this->currentStepId = $nextId;
        $this->history[] = $nextId;

        if ($definition->draftsEnabled) {
            $this->saveDraft($definition);
            $this->originalFormData = $this->formData;
        }

        $this->dispatch(
            FormEvents::WIZARD_STEP_ENTERED,
            ...EventPayload::make($definition->key, ['step_id' => $nextId])
        );
    }

    public function previousStep(): void
    {
        if (count($this->history) <= 1) {
            return;
        }

        array_pop($this->history);
        $lastKey = array_key_last($this->history);

        if ($lastKey !== null) {
            $this->currentStepId = $this->history[$lastKey];
        }
    }

    public function submit(): void
    {
        $definition = $this->resolveDefinition();
        $step = $definition->findStep($this->currentStepId);

        if ($step !== null) {
            $this->formData = $this->sanitizeAgainstFields($step['structure'], $this->formData, $this->originalFormData);
            $this->validate($this->rulesForStep($step));
        }

        try {
            if ($definition->saveUsing !== null) {
                ($definition->saveUsing)($this->formData);
            }
        } catch (\Throwable $e) {
            if ($definition->onSaveFailure !== null) {
                ($definition->onSaveFailure)($e);
            }

            throw $e;
        }

        if ($definition->draftsEnabled) {
            $this->forgetDraft($definition);
        }

        $this->completed = true;

        if ($definition->onSaveSuccess !== null) {
            ($definition->onSaveSuccess)($this->formData);
        }

        $this->dispatch(FormEvents::WIZARD_COMPLETED, ...EventPayload::make($definition->key));

        if ($definition->onSavedDispatchEvent !== null) {
            $this->dispatch(
                $definition->onSavedDispatchEvent,
                ...EventPayload::make($definition->key, $definition->onSavedDispatchPayload)
            );
        }

        if ($definition->completedRoute !== null) {
            $this->redirectRoute($definition->completedRoute);
        }
    }

    /** Whether formData has changed since mount (or the last draft save) — backs the dirty-navigation guard in the Blade view. */
    public function isDirty(): bool
    {
        return $this->formData !== $this->originalFormData;
    }

    private function resolveDefinition(): ArchitectWizardDefinition
    {
        return ($this->definitionClass)::definition();
    }

    /**
     * @param  array{id: string, label: string, structure: array<int, StructureItem>}  $step
     * @return array<string, array<int, string|ValidationRule>>
     */
    private function rulesForStep(array $step): array
    {
        $rules = [];

        foreach ($this->flattenFields($step['structure']) as $field) {
            $rules["formData.{$field->getName()}"] = $field->getRules();
        }

        return $rules;
    }

    /**
     * Cache keys a draft may be stored/looked up under: always the current
     * browser session (works immediately, no identifier required), plus —
     * once resumeUsingKey()'s formData key holds a value — a
     * session-independent key so the draft can also be resumed via a
     * stable identifier (e.g. a resume link) rather than only the
     * originating browser session.
     *
     * @return list<string>
     */
    private function draftStoreKeys(ArchitectWizardDefinition $definition): array
    {
        $keys = ['session:'.session()->getId().':'.$definition->key];

        if ($definition->resumeKey !== null) {
            $identifier = data_get($this->formData, $definition->resumeKey);

            if ($identifier !== null) {
                $keys[] = 'identified:'.$definition->key.':'.$identifier;
            }
        }

        return $keys;
    }

    /** @return array<string, mixed>|null */
    private function loadDraft(ArchitectWizardDefinition $definition): ?array
    {
        $store = app(WizardDraftStore::class);

        foreach ($this->draftStoreKeys($definition) as $key) {
            $draft = $store->get($key);

            if ($draft !== null) {
                return $draft;
            }
        }

        return null;
    }

    private function saveDraft(ArchitectWizardDefinition $definition): void
    {
        $store = app(WizardDraftStore::class);

        $payload = [
            'form_data' => $this->formData,
            'current_step_id' => $this->currentStepId,
            'history' => $this->history,
        ];

        foreach ($this->draftStoreKeys($definition) as $key) {
            $store->put($key, $payload);
        }

        $this->dispatch(
            FormEvents::WIZARD_DRAFT_SAVED,
            ...EventPayload::make($definition->key, ['step_id' => $this->currentStepId])
        );
    }

    private function forgetDraft(ArchitectWizardDefinition $definition): void
    {
        $store = app(WizardDraftStore::class);

        foreach ($this->draftStoreKeys($definition) as $key) {
            $store->forget($key);
        }
    }

    public function render(): View
    {
        $definition = $this->resolveDefinition();

        return view('architect::forms.wizard-engine', [
            'definition' => $definition,
            'currentStepId' => $this->currentStepId,
        ]);
    }
}
