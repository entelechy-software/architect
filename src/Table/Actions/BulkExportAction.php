<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Actions;

use Entelechy\Architect\Table\Contracts\ArchitectBulkAction;
use Entelechy\Architect\Table\Contracts\ArchitectDataModel;

/**
 * Marker bulk action: signals to the client that the selected rows
 * should be sent through the streaming exporter.
 *
 * The actual export is handled by a dedicated controller endpoint
 * (so it can stream a file response, which a Livewire action cannot),
 * not by the handle() method here. handle() returns the redirect URL
 * the client should navigate to with the selected ids attached.
 */
final class BulkExportAction implements ArchitectBulkAction
{
    /** @var array<string, mixed> */
    private array $options = [];

    public static function make(): self
    {
        return new self;
    }

    /**
     * @param  array<string, mixed>  $options  e.g. ['excel' => true, 'csv' => true]
     */
    public function withOptions(array $options): self
    {
        $clone = clone $this;
        $clone->options = $options;

        return $clone;
    }

    /** @return array<string, mixed> */
    public function options(): array
    {
        return $this->options;
    }

    public function getKey(): string
    {
        return 'export';
    }

    public function getLabel(): string
    {
        return 'Export';
    }

    /** @phpstan-ignore-next-line return.unusedType -- nullable per interface contract */
    public function icon(): ?string
    {
        return 'download';
    }

    public function color(): string
    {
        return 'primary';
    }

    public function confirm(): ?string
    {
        return null;
    }

    public function requiresReason(): bool
    {
        return false;
    }

    public function permissionNode(): ?string
    {
        // Export inherits the table's `read` node — the engine resolves
        // null to the appropriate default at invocation time.
        return null;
    }

    public function handle(array $selectedIds, ArchitectDataModel $dataModel, ?string $reason = null): array
    {
        // The Livewire engine intercepts this action and triggers a
        // browser navigation to the export controller; this handler
        // therefore reports back metadata only.
        return [
            'success' => true,
            'message' => 'Export started',
            'count' => count($selectedIds),
        ];
    }
}
