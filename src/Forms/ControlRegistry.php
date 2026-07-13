<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms;

use Entelechy\Architect\Forms\Contracts\ControlMetadata;

/**
 * Central catalog of every Forms control (field type), keyed by a short
 * registry key (e.g. 'text', 'currency', 'map-location').
 *
 * Bound as a container singleton (see ArchitectServiceProvider::boot()),
 * which seeds it with every Wave A (already-shipped) field plus every new
 * field this plan actually implements. This is what "the registry
 * formalizes Wave A rather than replacing it" (FORMS_FEATURE_PLAN.md
 * Phase 3) means concretely: existing Field subclasses are registered
 * as-is, unmodified, alongside their metadata.
 *
 * Accessed via Architect::controls().
 */
final class ControlRegistry
{
    /** @var array<string, ControlMetadata> */
    private array $controls = [];

    /**
     * @template TField of \Entelechy\Architect\Forms\Fields\Field
     *
     * @param  class-string<TField>  $fieldClass
     */
    public function register(string $key, string $fieldClass, string $category, string $valueType, string $description): static
    {
        $this->controls[$key] = new ControlDefinition($key, $fieldClass, $category, $valueType, $description);

        return $this;
    }

    public function has(string $key): bool
    {
        return isset($this->controls[$key]);
    }

    public function get(string $key): ?ControlMetadata
    {
        return $this->controls[$key] ?? null;
    }

    /** @return array<string, ControlMetadata> */
    public function all(): array
    {
        return $this->controls;
    }

    /** @return array<string, ControlMetadata> */
    public function byCategory(string $category): array
    {
        return array_filter($this->controls, static fn (ControlMetadata $c): bool => $c->category() === $category);
    }

    /** Clears every registered control. Intended for test isolation between cases. */
    public function reset(): void
    {
        $this->controls = [];
    }
}
