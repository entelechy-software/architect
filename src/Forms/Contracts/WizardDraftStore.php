<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Contracts;

/**
 * Persists in-progress wizard state so a user can leave and resume later
 * (WizardBuilder::drafts()). Bound to CacheWizardDraftStore by default —
 * see ArchitectServiceProvider::register().
 */
interface WizardDraftStore
{
    /** @param  array<string, mixed>  $payload */
    public function put(string $key, array $payload): void;

    /** @return array<string, mixed>|null */
    public function get(string $key): ?array;

    public function forget(string $key): void;
}
