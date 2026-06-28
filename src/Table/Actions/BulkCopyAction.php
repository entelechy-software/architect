<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Actions;

use Entelechy\Architect\Table\Contracts\ArchitectBulkAction;
use Entelechy\Architect\Table\Contracts\ArchitectDataModel;

/**
 * Signals the client to copy the selected rows to the clipboard in one or
 * more formats (TSV, Markdown table, CSV).
 *
 * Like BulkExportAction this is a client-side operation: handle() is never
 * called; the engine dispatches 'architect:copy' and the Blade template's
 * Alpine component executes the actual clipboard write.
 *
 * Options (set by the TableBuilder parser):
 *   clipboard — plain TSV copy (default true)
 *   markdown  — Markdown table copy
 *   csv       — RFC 4180 CSV copy
 */
final class BulkCopyAction implements ArchitectBulkAction
{
    /** @var array{clipboard: bool, markdown: bool, csv: bool} */
    private array $options = ['clipboard' => true, 'markdown' => false, 'csv' => false];

    public static function make(): self
    {
        return new self;
    }

    /**
     * @param  array{clipboard?: bool, markdown?: bool, csv?: bool}  $options
     */
    public function withOptions(array $options): self
    {
        $clone = clone $this;
        $clone->options = array_merge($clone->options, $options);

        return $clone;
    }

    /** @return array{clipboard: bool, markdown: bool, csv: bool} */
    public function options(): array
    {
        return $this->options;
    }

    public function getKey(): string
    {
        return 'copy';
    }

    public function getLabel(): string
    {
        return 'Copy';
    }

    /** @phpstan-ignore-next-line return.unusedType -- nullable per interface contract */
    public function icon(): ?string
    {
        return 'clipboard';
    }

    public function color(): string
    {
        return 'secondary';
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
        // Copy is a read-level operation — inherits the table's `read` node.
        return null;
    }

    /**
     * @param  array<int, int>  $selectedIds
     * @return array{success: bool, message: string, count: int}
     */
    public function handle(array $selectedIds, ArchitectDataModel $dataModel, ?string $reason = null): array
    {
        // The engine intercepts 'copy' and dispatches architect:copy instead.
        return [
            'success' => true,
            'message' => 'Copied '.count($selectedIds).' record(s) to clipboard.',
            'count' => count($selectedIds),
        ];
    }
}
