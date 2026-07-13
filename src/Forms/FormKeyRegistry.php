<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms;

use Entelechy\Architect\Forms\Exceptions\DuplicateFormKeyException;

/**
 * Detects form/wizard key collisions at runtime. Bound as a container
 * singleton (see ArchitectServiceProvider::register()) so every
 * FormEngine/WizardEngine mounted within the same request shares one
 * registry instance — this is what actually catches "two forms with the
 * same key on one page" (see FORMS_API_COMPATIBILITY_CONTRACT.md).
 *
 * For project-wide (not just same-request) detection, see the
 * `architect:forms:audit-keys` console command.
 */
final class FormKeyRegistry
{
    /** @var array<string, class-string> */
    private array $keys = [];

    /**
     * @param  class-string  $definitionClass
     *
     * @throws DuplicateFormKeyException
     */
    public function register(string $key, string $definitionClass): void
    {
        if (isset($this->keys[$key]) && $this->keys[$key] !== $definitionClass) {
            throw new DuplicateFormKeyException(
                "Form key '{$key}' is already registered to {$this->keys[$key]}; ".
                "{$definitionClass} attempted to reuse it. Form keys must be globally ".
                'unique — give one of these definitions a distinct key.'
            );
        }

        $this->keys[$key] = $definitionClass;
    }

    /** @return array<string, class-string> */
    public function all(): array
    {
        return $this->keys;
    }

    /** Clears all registered keys. Intended for test isolation between cases. */
    public function reset(): void
    {
        $this->keys = [];
    }
}
