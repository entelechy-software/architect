<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Livewire;

use Entelechy\Architect\Forms\ArchitectFormDefinition;
use Entelechy\Architect\Forms\Contracts\ArchitectField;
use Entelechy\Architect\Forms\Contracts\StructureItem;
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
    use WithFileUploads;

    public string $definitionClass;

    /** @var array<string, mixed> */
    public array $formData = [];

    public bool $submitting = false;

    public bool $justSaved = false;

    public function mount(string $definitionClass): void
    {
        $this->definitionClass = $definitionClass;
        $definition = $this->resolveDefinition();

        foreach ($this->flattenFields($definition->structure) as $field) {
            $this->formData[$field->getName()] = $field->getDefault();
        }

        if ($definition->fillData !== null) {
            $this->applyFillData($definition->fillData);
        }
    }

    public function submit(): void
    {
        $definition = $this->resolveDefinition();
        $rules = $this->buildValidationRules($definition);
        $this->validate($rules);

        $this->submitting = true;

        if ($definition->beforeSave !== null) {
            ($definition->beforeSave)($this->formData);
        }

        if ($definition->saveUsing !== null) {
            ($definition->saveUsing)($this->formData);
        }

        if ($definition->afterSave !== null) {
            ($definition->afterSave)($this->formData);
        }

        $this->submitting = false;
        $this->justSaved = true;

        $this->dispatch('architect:form:saved');

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

        if ($definition->saveUsing !== null) {
            ($definition->saveUsing)($this->formData);
        }

        if ($definition->afterSave !== null) {
            ($definition->afterSave)($this->formData);
        }

        $this->dispatch('architect:form:autosaved');
    }

    private function resolveDefinition(): ArchitectFormDefinition
    {
        return ($this->definitionClass)::definition();
    }

    private function applyFillData(mixed $data): void
    {
        $source = is_object($data) ? (method_exists($data, 'toArray') ? $data->toArray() : (array) $data) : (array) $data;
        $this->formData = array_merge($this->formData, $source);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function buildValidationRules(ArchitectFormDefinition $definition): array
    {
        $rules = [];

        foreach ($this->flattenFields($definition->structure) as $field) {
            $rules["formData.{$field->getName()}"] = $field->getRules();
        }

        return $rules;
    }

    /**
     * Recursively flattens Section/Grid/Fieldset containers into their
     * leaf ArchitectField items.
     *
     * @param  array<int, StructureItem>  $structure
     * @return array<int, ArchitectField>
     */
    private function flattenFields(array $structure): array
    {
        $fields = [];

        foreach ($structure as $item) {
            if ($item instanceof ArchitectField) {
                $fields[] = $item;

                continue;
            }

            if (method_exists($item, 'getStructure')) {
                $fields = array_merge($fields, $this->flattenFields($item->getStructure()));
            }
        }

        return $fields;
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
