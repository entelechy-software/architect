<?php

declare(strict_types=1);

namespace Entelechy\Architect\Actions\Actions;

/**
 * Opens a form to edit the resolved record.
 *
 * When a formClass is provided, the ActionEngine dispatches an
 * 'architect:action:open-form' browser event so a slide-over or modal
 * can render the specified FormEngine pre-filled with record data.
 */
class EditAction extends Action
{
    protected string $label = 'Edit';

    protected string $color = 'primary';

    protected ?string $formClass = null;

    /** @var array<int, mixed> */
    protected array $formStructure = [];

    /** Provide a host-app FormEngine definition class FQCN. */
    public function formClass(string $class): static
    {
        $clone = clone $this;
        $clone->formClass = $class;

        return $clone;
    }

    /**
     * Inline form structure (array of StructureItem).
     *
     * @param  array<int, mixed>  $items
     */
    public function form(array $items): static
    {
        $clone = clone $this;
        $clone->formStructure = $items;

        return $clone;
    }

    public function getFormClass(): ?string
    {
        return $this->formClass;
    }

    /**
     * @return array<int, mixed>
     */
    public function getFormStructure(): array
    {
        return $this->formStructure !== [] ? $this->formStructure : $this->defaultFormStructure();
    }

    /**
     * Override in a host-app subclass to provide the inline form structure
     * without going through ->form([...]) — Action's constructor is final,
     * so this is the supported extension point for property-style config.
     *
     * @return array<int, mixed>
     */
    protected function defaultFormStructure(): array
    {
        return [];
    }

    /** @param array<string, mixed> $data */
    public function run(mixed $record, array $data = []): void
    {
        if ($this->actionCallback !== null) {
            parent::run($record, $data);

            return;
        }

        // Default: no-op — the form slide-over handles record editing.
    }
}
