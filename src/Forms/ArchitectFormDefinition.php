<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms;

use Closure;
use Entelechy\Architect\Forms\Contracts\StructureItem;

/**
 * Frozen value object produced by FormBuilder::build().
 *
 * Consumed by Forms\Livewire\FormEngine — never constructed directly by
 * host-app code.
 */
final class ArchitectFormDefinition
{
    /**
     * @param  array<int, StructureItem>  $structure
     * @param  array<string, mixed>  $onSavedDispatchPayload
     */
    public function __construct(
        public readonly string $key,
        public readonly array $structure,
        public readonly ?Closure $saveUsing,
        public readonly mixed $fillData,
        public readonly ?Closure $beforeSave = null,
        public readonly ?Closure $afterSave = null,
        public readonly ?string $redirectAfterSave = null,
        public readonly ?int $autosaveInterval = null,
        public readonly ?string $onSavedDispatchEvent = null,
        public readonly array $onSavedDispatchPayload = [],
        public readonly ?Closure $onSaveSuccess = null,
        public readonly ?Closure $onSaveFailure = null,
        public readonly ?string $supersearchLabel = null,
    ) {}
}
