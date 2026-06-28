<?php

declare(strict_types=1);

namespace Entelechy\Architect\Supersearch\Actions\Contracts;

interface SearchAction
{
    public function type(): string;

    /**
     * Serialise to a plain array for Livewire snapshots and Blade.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
