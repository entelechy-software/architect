<?php

declare(strict_types=1);

namespace Entelechy\Architect\Support\Redaction;

/**
 * Fluent redaction API shared by Table\Column and Forms\Fields\Field.
 *
 * Mirrors the immutable-clone convention already used throughout both
 * hierarchies (see Column::badge(), Field::hidden(), etc.) — every
 * setter returns a clone rather than mutating in place.
 *
 * Permission checks always use a single permission-node string (never
 * a raw closure), consistent with every other permission-gated concern
 * in the codebase (Column::visibleTo(), Column::toggleable(), Field::permission()).
 * Evaluation of that node against the current user is the caller's
 * responsibility (Table\Permissions\RedactionFilter for Column;
 * PermissionResolver directly for Forms fields) — this trait only
 * stores configuration and applies the masking strategy itself.
 */
trait Redactable
{
    private bool $redacted = false;

    private ?RedactionStrategy $redactionStrategy = null;

    /** Permission node that bypasses redaction entirely (full value shown). */
    private ?string $redactUnlessPermission = null;

    /** Whether a "reveal" affordance is offered to users holding $revealPermission. */
    private bool $revealable = false;

    /** Permission node required to reveal the value on demand. Falls back to $redactUnlessPermission. */
    private ?string $revealPermission = null;

    /**
     * Mark this value as sensitive. Masked according to $strategy unless
     * the current user holds the {@see redactUnless()} permission node.
     */
    public function redact(string|RedactionStrategy $strategy = 'partial'): static
    {
        $clone = clone $this;
        $clone->redacted = true;
        $clone->redactionStrategy = is_string($strategy) ? RedactionStrategy::preset($strategy) : $strategy;

        return $clone;
    }

    /** Permission node that, when held, shows the real value instead of the mask. */
    public function redactUnless(string $permission): static
    {
        $clone = clone $this;
        $clone->redactUnlessPermission = $permission;

        return $clone;
    }

    /**
     * Offer a click-to-reveal affordance to users holding $permission
     * (defaults to the redactUnless() node when omitted). Has no effect
     * unless redact() is also called.
     */
    public function revealable(?string $permission = null): static
    {
        $clone = clone $this;
        $clone->revealable = true;
        $clone->revealPermission = $permission;

        return $clone;
    }

    public function isRedacted(): bool
    {
        return $this->redacted;
    }

    public function getRedactionStrategy(): RedactionStrategy
    {
        return $this->redactionStrategy ??= RedactionStrategy::partial();
    }

    public function getRedactUnlessPermission(): ?string
    {
        return $this->redactUnlessPermission;
    }

    public function isRevealable(): bool
    {
        return $this->revealable;
    }

    public function getRevealPermission(): ?string
    {
        return $this->revealPermission ?? $this->redactUnlessPermission;
    }

    public function applyRedaction(mixed $value): string
    {
        return $this->getRedactionStrategy()->apply($value);
    }
}
