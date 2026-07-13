<?php

declare(strict_types=1);

namespace Entelechy\Architect\Actions\Livewire;

use Entelechy\Architect\Actions\Actions\CreateAction;
use Entelechy\Architect\Actions\Actions\EditAction;
use Entelechy\Architect\Actions\Actions\ViewAction;
use Entelechy\Architect\Actions\Contracts\ArchitectAction;
use Entelechy\Architect\Forms\ArchitectWizardDefinition;
use Entelechy\Architect\Forms\Contracts\ArchitectField;
use Entelechy\Architect\Forms\Contracts\StructureItem;
use Entelechy\Architect\Forms\Events\FormEvents;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Livewire engine that handles action dispatch, confirmation dialogs,
 * inline/reusable-class form panels, read-only content panels, and
 * execution for all Architect actions.
 *
 * The host blade template dispatches:
 *
 *   $dispatch('architect:action:trigger', {
 *       actionClass: 'App\\Actions\\DeleteMemberAction',
 *       recordId: 42
 *   })
 *
 * ActionEngine validates the class is a legitimate ArchitectAction, then
 * routes to one of four panels depending on the action's configuration:
 *   - CreateAction/EditAction with ->formClass()  → slide-over hosting FormEngine
 *   - CreateAction/EditAction with ->form([...])  → slide-over with inline fields
 *   - ViewAction with ->contentClass()             → slide-over hosting ContentEngine
 *   - everything else                              → confirm-then-run (or run directly)
 *
 * Registration: 'architect-action-engine'
 * Usage: <livewire:architect-action-engine />
 */
class ActionEngine extends Component
{
    public ?string $activeActionClass = null;

    public ?int $activeRecordId = null;

    public bool $showConfirmation = false;

    /** Which non-confirmation panel is open, if any. */
    public ?string $openPanel = null; // 'form-class' | 'inline-form' | 'content-class' | null

    /** @var array<string, mixed> */
    public array $formData = [];

    #[On('architect:action:trigger')]
    public function triggerAction(string $actionClass, ?int $recordId = null): void
    {
        if (! class_exists($actionClass) || ! is_a($actionClass, ArchitectAction::class, true)) {
            return;
        }

        $this->activeActionClass = $actionClass;
        $this->activeRecordId = $recordId;
        $this->formData = [];
        $this->openPanel = null;

        /** @var ArchitectAction $action */
        $action = new $actionClass;

        if ($action instanceof ViewAction) {
            if ($action->getContentClass() !== null) {
                $this->openPanel = 'content-class';

                return;
            }

            // No content class configured — nothing to show.
            $this->reset(['activeActionClass', 'activeRecordId']);

            return;
        }

        if ($action instanceof CreateAction || $action instanceof EditAction) {
            if ($action->getFormClass() !== null) {
                $this->openPanel = 'form-class';

                return;
            }

            $structure = $action->getFormStructure();
            if ($structure !== []) {
                $record = $action instanceof EditAction && $recordId !== null
                    ? $action->resolveRecord($recordId)
                    : null;

                foreach ($this->flattenFields($structure) as $field) {
                    $this->formData[$field->getName()] = $record !== null
                        ? data_get($record, $field->getName(), $field->getDefault())
                        : $field->getDefault();
                }

                $this->openPanel = 'inline-form';

                return;
            }
        }

        if ($action->isConfirmationRequired()) {
            $this->showConfirmation = true;
        } else {
            $this->execute();
        }
    }

    public function confirmAndRun(): void
    {
        $this->showConfirmation = false;
        $this->execute();
    }

    public function cancelConfirmation(): void
    {
        $this->showConfirmation = false;
        $this->activeActionClass = null;
        $this->activeRecordId = null;
    }

    /**
     * The nested FormEngine (mounted for the 'form-class' panel) dispatches
     * this on successful save — its own saveUsing() closure owns the actual
     * persistence, so closing the panel is all ActionEngine needs to do.
     */
    #[On(FormEvents::SAVED)]
    public function onNestedFormSaved(): void
    {
        if ($this->openPanel === 'form-class') {
            $this->closePanel();
            $this->dispatch('architect:action:completed');
        }
    }

    /**
     * Mirrors onNestedFormSaved() for the case where ->formClass() resolves
     * to an ArchitectWizardDefinition rather than a plain form — the
     * nested WizardEngine dispatches this on final-step submit.
     */
    #[On(FormEvents::WIZARD_COMPLETED)]
    public function onNestedWizardCompleted(): void
    {
        if ($this->openPanel === 'form-class') {
            $this->closePanel();
            $this->dispatch('architect:action:completed');
        }
    }

    /**
     * Whether ->formClass()'s definition() is a wizard rather than a
     * standalone form — determines which engine component the 'form-class'
     * panel mounts (see resources/views/actions/engine.blade.php).
     *
     * @param  class-string  $formClass
     */
    public function formClassIsWizard(string $formClass): bool
    {
        return $formClass::definition() instanceof ArchitectWizardDefinition;
    }

    public function closePanel(): void
    {
        $this->openPanel = null;
        $this->activeActionClass = null;
        $this->activeRecordId = null;
        $this->formData = [];
    }

    /**
     * Submit handler for the 'inline-form' panel (CreateAction/EditAction
     * configured via ->form([...]) rather than ->formClass()).
     */
    public function submitInlineForm(): void
    {
        if ($this->activeActionClass === null) {
            return;
        }

        /** @var ArchitectAction $action */
        $action = new $this->activeActionClass;

        if (! ($action instanceof CreateAction || $action instanceof EditAction)) {
            return;
        }

        $fields = $this->flattenFields($action->getFormStructure());

        $rules = [];
        foreach ($fields as $field) {
            $rules["formData.{$field->getName()}"] = $field->getRules();
        }
        $this->validate($rules);

        $record = $action instanceof EditAction && $this->activeRecordId !== null
            ? $action->resolveRecord($this->activeRecordId)
            : null;

        if (! $action->canRun($record)) {
            $this->dispatch('architect:action:unauthorized');

            return;
        }

        $action->run($record, $this->formData);

        $this->closePanel();
        $this->dispatch('architect:action:completed');
    }

    private function execute(): void
    {
        if ($this->activeActionClass === null) {
            return;
        }

        /** @var ArchitectAction $action */
        $action = new $this->activeActionClass;

        $record = $this->activeRecordId !== null
            ? $action->resolveRecord($this->activeRecordId)
            : null;

        if (! $action->canRun($record)) {
            $this->dispatch('architect:action:unauthorized');

            return;
        }

        $action->run($record, $this->formData);

        $this->activeActionClass = null;
        $this->activeRecordId = null;
        $this->formData = [];

        $this->dispatch('architect:action:completed');
    }

    /**
     * Recursively flattens Section/Grid/Fieldset containers into their
     * leaf ArchitectField items — mirrors Forms\Livewire\FormEngine.
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
        $action = null;
        $structure = [];

        if ($this->activeActionClass !== null && class_exists($this->activeActionClass)) {
            /** @var ArchitectAction $action */
            $action = new $this->activeActionClass;

            if ($this->openPanel === 'inline-form' && ($action instanceof CreateAction || $action instanceof EditAction)) {
                $structure = $action->getFormStructure();
            }
        }

        return view('architect::actions.engine', [
            'action' => $action,
            'structure' => $structure,
        ]);
    }
}
