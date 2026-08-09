<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Livewire;

use Entelechy\Architect\Forms\ArchitectFormDefinition;
use Entelechy\Architect\Forms\Concerns\FlattensStructure;
use Entelechy\Architect\Forms\Concerns\SanitizesFormData;
use Entelechy\Architect\Forms\Contracts\ProvidesFormDefinition;
use Entelechy\Architect\Forms\Events\EventPayload;
use Entelechy\Architect\Forms\Events\FormEvents;
use Entelechy\Architect\Forms\FormKeyRegistry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Standalone form renderer/validator/submitter.
 *
 * Mounted with the FQCN of a host-app class exposing a static
 * `definition(): ArchitectFormDefinition` method. Blade usage:
 *
 *   {{ '<livewire:architect-form-engine :definition-class="\App\Forms\MemberForm::class" />' }}
 *
 * Note: the toast notification call described in the Phase 4 plan depends
 * on the Notifications subsystem built in Phase 9, which does not exist
 * yet. Until then, FormEngine dispatches the `architect:form:saved`
 * browser event on success so host apps (or a future Architect::toast()
 * listener) can react to it.
 */
class FormEngine extends Component
{
    use FlattensStructure;
    use SanitizesFormData;
    use WithFileUploads;

    /** @var class-string */
    public string $definitionClass;

    /** @var array<string, mixed> */
    public array $formData = [];

    /**
     * Snapshot of formData immediately after mount()'s initial fill, before
     * any user interaction. Used by SanitizesFormData to revert
     * disabled/permission-gated fields to their pre-existing value rather
     * than trusting whatever the client submitted for them.
     *
     * @var array<string, mixed>
     */
    protected array $originalFormData = [];

    public bool $submitting = false;

    public bool $justSaved = false;

    /** @param  class-string  $definitionClass */
    public function mount(string $definitionClass): void
    {
        $this->definitionClass = $definitionClass;
        $definition = $this->resolveDefinition();

        app(FormKeyRegistry::class)->register($definition->key, $definitionClass);

        foreach ($this->flattenFields($definition->structure) as $field) {
            $this->formData[$field->getName()] = $field->getDefault();
        }

        if ($definition->fillData !== null) {
            $this->applyFillData($definition->fillData);
        }

        $this->originalFormData = $this->formData;
    }

    public function submit(): void
    {
        $definition = $this->resolveDefinition();
        $this->formData = $this->sanitizeAgainstFields($definition->structure, $this->formData, $this->originalFormData);
        $rules = $this->buildValidationRules($definition);
        $this->validate($rules);

        $this->submitting = true;

        if ($definition->beforeSave !== null) {
            ($definition->beforeSave)($this->formData);
        }

        try {
            if ($definition->saveUsing !== null) {
                ($definition->saveUsing)($this->formData);
            }
        } catch (\Throwable $e) {
            $this->submitting = false;

            if ($definition->onSaveFailure !== null) {
                ($definition->onSaveFailure)($e);
            }

            throw $e;
        }

        if ($definition->afterSave !== null) {
            ($definition->afterSave)($this->formData);
        }

        $this->submitting = false;
        $this->justSaved = true;

        if ($definition->onSaveSuccess !== null) {
            ($definition->onSaveSuccess)($this->formData);
        }

        $this->dispatch(FormEvents::SAVED, ...EventPayload::make($definition->key));

        if ($definition->onSavedDispatchEvent !== null) {
            $this->dispatch(
                $definition->onSavedDispatchEvent,
                ...EventPayload::make($definition->key, $definition->onSavedDispatchPayload)
            );
        }

        if ($definition->redirectAfterSave !== null) {
            $this->redirectRoute($definition->redirectAfterSave);
        }
    }

    /** Called by wire:poll when autosave is configured. */
    public function autosave(): void
    {
        $definition = $this->resolveDefinition();

        if ($definition->autosaveInterval === null) {
            return;
        }

        $this->formData = $this->sanitizeAgainstFields($definition->structure, $this->formData, $this->originalFormData);
        $rules = $this->buildValidationRules($definition);

        try {
            $this->validate($rules);
        } catch (ValidationException) {
            // Autosave silently skips invalid state.
            return;
        }

        if ($definition->beforeSave !== null) {
            ($definition->beforeSave)($this->formData);
        }

        try {
            if ($definition->saveUsing !== null) {
                ($definition->saveUsing)($this->formData);
            }
        } catch (\Throwable $e) {
            // Autosave failures are non-blocking by design (see
            // FORMS_FEATURE_PLAN.md Phase 5) — surfaced via the failure
            // hook if registered, but never thrown to disrupt the poll.
            if ($definition->onSaveFailure !== null) {
                ($definition->onSaveFailure)($e);
            }

            return;
        }

        if ($definition->afterSave !== null) {
            ($definition->afterSave)($this->formData);
        }

        if ($definition->onSaveSuccess !== null) {
            ($definition->onSaveSuccess)($this->formData);
        }

        $this->dispatch(FormEvents::AUTOSAVED, ...EventPayload::make($definition->key));
    }

    private function resolveDefinition(): ArchitectFormDefinition
    {
        $class = $this->definitionClass;

        if (! class_exists($class) || ! is_subclass_of($class, ProvidesFormDefinition::class)) {
            throw new \LogicException("FormEngine: '{$class}' must implement ".ProvidesFormDefinition::class);
        }

        return $class::definition();
    }

    private function applyFillData(mixed $data): void
    {
        $source = is_object($data) ? (method_exists($data, 'toArray') ? $data->toArray() : (array) $data) : (array) $data;
        $this->formData = array_merge($this->formData, $source);
    }

    /**
     * @return array<string, array<int, string|ValidationRule>>
     */
    private function buildValidationRules(ArchitectFormDefinition $definition): array
    {
        $rules = [];

        foreach ($this->flattenFields($definition->structure) as $field) {
            $rules["formData.{$field->getName()}"] = $field->getRules();
        }

        return $rules;
    }

    public function render(): View
    {
        $definition = $this->resolveDefinition();
        $get = fn (string $field): mixed => data_get($this->formData, $field);

        return view('architect::forms.engine', [
            'definition' => $definition,
            'get' => $get,
        ]);
    }
}
