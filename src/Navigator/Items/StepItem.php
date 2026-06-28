<?php

declare(strict_types=1);

namespace Entelechy\Architect\Navigator\Items;

use Entelechy\Architect\Concerns\HasOpenInTab;
use Entelechy\Architect\Navigator\Behaviours\LinkBehaviour;
use Entelechy\Architect\Navigator\Contracts\NavigatorItem;

/**
 * A step item for stepper-style navigators.
 *
 * Renders as a numbered step with label, icon, and optional sub-label.
 * Phase A: link behaviour only.
 * Phase B: validateOnStep — mark steps complete via completed(bool) so
 *           the stepper can lock steps that haven't been reached yet.
 */
final class StepItem implements NavigatorItem
{
    use HasOpenInTab;

    private ?string $icon = null;

    private ?string $subLabel = null;

    private ?string $href = null;

    private ?LinkBehaviour $behaviour = null;

    private bool $isDefault = false;

    private bool $disabled = false;

    private bool $completed = false;

    /**
     * Step number (1-based). Not set by the caller — assigned by NavigatorBuilder
     * in declaration order during build(). Access via getStep() after the
     * definition has been built.
     */
    private int $step = 0;

    private function __construct(
        private readonly string $label,
    ) {}

    public static function make(string $label): self
    {
        return new self($label);
    }

    /**
     * Called by NavigatorBuilder::build() to assign the 1-based step number.
     * Not intended for direct use by callers.
     */
    public function withStep(int $n): self
    {
        $clone = clone $this;
        $clone->step = $n;

        return $clone;
    }

    public function icon(string $icon): self
    {
        $clone = clone $this;
        $clone->icon = $icon;

        return $clone;
    }

    public function subLabel(string $subLabel): self
    {
        $clone = clone $this;
        $clone->subLabel = $subLabel;

        return $clone;
    }

    public function href(string $url): self
    {
        $clone = clone $this;
        $clone->href = $url;
        $clone->behaviour = new LinkBehaviour($url);

        return $clone;
    }

    public function behaviour(LinkBehaviour $behaviour): self
    {
        $clone = clone $this;
        $clone->behaviour = $behaviour;
        if ($clone->href === null) {
            $clone->href = $behaviour->url;
        }

        return $clone;
    }

    public function default(): self
    {
        $clone = clone $this;
        $clone->isDefault = true;

        return $clone;
    }

    public function disabled(): self
    {
        $clone = clone $this;
        $clone->disabled = true;

        return $clone;
    }

    /**
     * Mark this step as completed.
     *
     * Used with NavigatorBuilder::validateOnStep() to determine which
     * subsequent steps should be locked. A completed step stays clickable
     * so users can return and amend their answers.
     */
    public function completed(bool $done = true): self
    {
        $clone = clone $this;
        $clone->completed = $done;

        return $clone;
    }

    // ── NavigatorItem contract ───────────────────────────────────────────

    public function getItemType(): string
    {
        return 'step';
    }

    // ── Accessors ────────────────────────────────────────────────────────

    public function getStep(): int
    {
        return $this->step;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getSubLabel(): ?string
    {
        return $this->subLabel;
    }

    public function getHref(): ?string
    {
        return $this->href;
    }

    public function getBehaviour(): ?LinkBehaviour
    {
        return $this->behaviour;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function isActiveForPath(string $path): bool
    {
        if ($this->href === null) {
            return false;
        }

        $normalized = rtrim($this->href, '/');

        return $normalized !== '' && str_starts_with(rtrim($path, '/'), $normalized);
    }
}
