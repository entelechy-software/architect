<?php

declare(strict_types=1);

namespace Entelechy\Architect\Actions\Actions;

/**
 * Opens a read-only content panel for the resolved record.
 *
 * Dispatches an 'architect:action:open-content' browser event with the
 * contentClass so a slide-over can render the ContentEngine.
 */
class ViewAction extends Action
{
    protected string $label = 'View';

    protected string $color = 'secondary';

    protected ?string $contentClass = null;

    /** Provide a host-app ContentEngine definition class FQCN. */
    public function contentClass(string $class): static
    {
        $clone = clone $this;
        $clone->contentClass = $class;

        return $clone;
    }

    public function getContentClass(): ?string
    {
        return $this->contentClass;
    }

    /** @param array<string, mixed> $data */
    public function run(mixed $record, array $data = []): void
    {
        if ($this->actionCallback !== null) {
            parent::run($record, $data);

            return;
        }

        // Default: no-op — the content slide-over handles record viewing.
    }
}
