<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Livewire;

use Entelechy\Architect\Forms\ArchitectWizardDefinition;
use Entelechy\Architect\Forms\Contracts\ArchitectField;
use Entelechy\Architect\Forms\Contracts\StructureItem;
use Illuminate\View\View;
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
 */
class WizardEngine extends Component
{
    public string $definitionClass;

    public int $currentStep = 1;

    /** @var array<string, mixed> */
    public array $formData = [];

    public bool $completed = false;

    public function mount(string $definitionClass): void
    {
        $this->definitionClass = $definitionClass;
        $definition = $this->resolveDefinition();

        // Pre-fill all fields from all steps with their defaults.
        foreach ($definition->steps as $step) {
            foreach ($this->flattenFields($step['structure']) as $field) {
                $this->formData[$field->getName()] = $field->getDefault();
            }
        }
    }

    public function nextStep(): void
    {
        $definition = $this->resolveDefinition();
        $totalSteps = $definition->totalSteps();

        if ($this->currentStep >= $totalSteps) {
            return;
        }

        $stepIndex = $this->currentStep - 1;
        $currentStructure = $definition->steps[$stepIndex]['structure'] ?? [];
        $rules = [];

        foreach ($this->flattenFields($currentStructure) as $field) {
            $rules["formData.{$field->getName()}"] = $field->getRules();
        }

        $this->validate($rules);

        $this->currentStep++;
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function submit(): void
    {
        $definition = $this->resolveDefinition();

        // Validate the final step.
        $stepIndex = $this->currentStep - 1;
        $currentStructure = $definition->steps[$stepIndex]['structure'] ?? [];
        $rules = [];

        foreach ($this->flattenFields($currentStructure) as $field) {
            $rules["formData.{$field->getName()}"] = $field->getRules();
        }

        $this->validate($rules);

        if ($definition->saveUsing !== null) {
            ($definition->saveUsing)($this->formData);
        }

        $this->completed = true;
        $this->dispatch('architect:wizard:completed');

        if ($definition->completedRoute !== null) {
            $this->redirectRoute($definition->completedRoute);
        }
    }

    private function resolveDefinition(): ArchitectWizardDefinition
    {
        return ($this->definitionClass)::definition();
    }

    /**
     * Recursively flattens Section/Grid/Fieldset containers into leaf ArchitectField items.
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

        return view('architect::forms.wizard-engine', [
            'definition' => $definition,
            'currentStep' => $this->currentStep,
        ]);
    }
}
