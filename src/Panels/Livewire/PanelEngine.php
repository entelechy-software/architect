<?php

declare(strict_types=1);

namespace Entelechy\Architect\Panels\Livewire;

use Entelechy\Architect\Forms\Contracts\ArchitectField;
use Entelechy\Architect\Forms\Contracts\StructureItem;
use Entelechy\Architect\Forms\Events\EventPayload;
use Entelechy\Architect\Panels\ArchitectDashboardDefinition;
use Entelechy\Architect\Panels\Panels\QuickFormPanel;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Renders an Architect dashboard — a grid of typed panels.
 *
 * Mounted with the FQCN of a host-app class that exposes a static
 * `definition(): ArchitectDashboardDefinition` method. Blade usage:
 *
 *   <livewire:architect-panel-engine definition-class="\App\Dashboards\HomeDashboard" />
 *
 * Registration: 'architect-panel-engine'
 */
class PanelEngine extends Component
{
    public string $definitionClass;

    /**
     * QuickFormPanel form state. Field Blade views (shared with FormEngine)
     * hardcode `wire:model="formData.{name}"`, so this property uses the
     * same flat shape rather than nesting per panel index — a dashboard
     * with multiple QuickFormPanels shares one formData bag, which is the
     * supported pattern for now (most dashboards embed at most one).
     *
     * @var array<string, mixed>
     */
    public array $formData = [];

    /** @var array<int, bool> */
    public array $quickFormSuccess = [];

    public function mount(string $definitionClass): void
    {
        $this->definitionClass = $definitionClass;
    }

    private function resolveDefinition(): ArchitectDashboardDefinition
    {
        return ($this->definitionClass)::definition();
    }

    public function submitQuickForm(int $panelIndex): void
    {
        $definition = $this->resolveDefinition();
        $slot = $definition->panels[$panelIndex] ?? null;

        if ($slot === null || ! ($slot['panel'] instanceof QuickFormPanel)) {
            return;
        }

        $panel = $slot['panel'];
        $fields = $this->flattenFields($panel->getStructure());

        $rules = [];
        foreach ($fields as $field) {
            $rules["formData.{$field->getName()}"] = $field->getRules();
        }
        $this->validate($rules);

        $saveUsing = $panel->getSaveUsing();

        try {
            if ($saveUsing !== null) {
                $saveUsing($this->formData);
            }
        } catch (\Throwable $e) {
            $onSaveFailure = $panel->getOnSaveFailure();
            if ($onSaveFailure !== null) {
                $onSaveFailure($e);
            }

            throw $e;
        }

        $onSaveSuccess = $panel->getOnSaveSuccess();
        if ($onSaveSuccess !== null) {
            $onSaveSuccess($this->formData);
        }

        if ($panel->getOnSavedDispatchEvent() !== null) {
            $this->dispatch(
                $panel->getOnSavedDispatchEvent(),
                ...EventPayload::make('panel-'.$panelIndex, $panel->getOnSavedDispatchPayload())
            );
        }

        $this->quickFormSuccess[$panelIndex] = true;
        foreach ($fields as $field) {
            $this->formData[$field->getName()] = $field->getDefault();
        }
    }

    /**
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

        foreach ($definition->panels as $slot) {
            if (! $slot['panel'] instanceof QuickFormPanel) {
                continue;
            }

            foreach ($this->flattenFields($slot['panel']->getStructure()) as $field) {
                if (! array_key_exists($field->getName(), $this->formData)) {
                    $this->formData[$field->getName()] = $field->getDefault();
                }
            }
        }

        $get = fn (string $field): mixed => data_get($this->formData, $field);

        return view('architect::panels.engine', [
            'definition' => $definition,
            'get' => $get,
        ]);
    }
}
