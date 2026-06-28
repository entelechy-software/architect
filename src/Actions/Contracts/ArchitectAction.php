<?php

declare(strict_types=1);

namespace Entelechy\Architect\Actions\Contracts;

/**
 * Contract every Architect action must satisfy.
 *
 * Concrete implementations extend the abstract Action base class and override
 * protected properties for static configuration, or inject dynamic logic via
 * the authorize() / action() callbacks.
 *
 * The ActionEngine validates that any class name received from the browser
 * implements this interface before instantiation, preventing arbitrary class
 * execution.
 */
interface ArchitectAction
{
    public function getKey(): string;

    public function getLabel(): string;

    public function getIcon(): ?string;

    public function getColor(): string;

    public function isDestructive(): bool;

    public function isConfirmationRequired(): bool;

    public function getConfirmationTitle(): string;

    public function getConfirmationMessage(): string;

    public function canRun(mixed $record): bool;

    /** @param array<string, mixed> $data */
    public function run(mixed $record, array $data = []): void;

    public function resolveRecord(int $id): mixed;
}
