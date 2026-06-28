<?php

declare(strict_types=1);

namespace Entelechy\Architect\Navigator\Items;

use Entelechy\Architect\Table\Livewire\Engine;

/**
 * Registers a type of dynamic tab that can be opened at runtime.
 *
 * Dynamic tabs are opened by any component dispatching the
 * `architect:open-record` event. The type string must match the `type`
 * field in that event payload.
 *
 * Usage:
 *   DynamicTabType::make('case')
 *       ->component('advice.case-detail')
 *       ->icon('fas fa-folder')
 *       ->labelResolver(fn(array $props) => 'Case #' . $props['id'])
 */
final class DynamicTabType
{
    private string $component = '';

    private ?string $definitionClass = null;

    private ?string $icon = null;

    /** @var (callable(array<string, mixed>): string)|null */
    private $labelResolver = null;

    /**
     * Optional factory invoked when the tab is opened with no props (i.e. a
     * "create new record" intent). Receives no arguments and must return a
     * props array that includes at minimum the fields required to mount the
     * component (typically ['id' => $newRecordId]).
     *
     * @var (callable(): array<string, mixed>)|null
     */
    private $createUsing = null;

    private function __construct(
        private readonly string $type,
    ) {}

    public static function make(string $type): self
    {
        return new self($type);
    }

    /**
     * Livewire component alias to mount for this tab type.
     */
    public function component(string $component): self
    {
        $clone = clone $this;
        $clone->component = $component;

        return $clone;
    }

    /**
     * Embed an Architect table definition in this tab type.
     *
     * Shorthand for ->component(Engine::class)->props(...).
     * The FQCN must point to a class whose ::definition() returns an
     * ArchitectTableDefinition.
     */
    public function architect(string $definitionClass): self
    {
        $clone = clone $this;
        $clone->component = Engine::class;
        $clone->definitionClass = $definitionClass;

        return $clone;
    }

    /**
     * Font Awesome icon class shown in the tab bar.
     */
    public function icon(string $icon): self
    {
        $clone = clone $this;
        $clone->icon = $icon;

        return $clone;
    }

    /**
     * Callable that receives the tab's props and returns a label string.
     * Defaults to the type name if not set.
     *
     * @param  callable(array<string, mixed>): string  $resolver
     */
    public function labelResolver(callable $resolver): self
    {
        $clone = clone $this;
        $clone->labelResolver = $resolver;

        return $clone;
    }

    /**
     * Register a factory that creates a new record and returns its props.
     *
     * Called automatically by ModuleTabsManager when a architect:open-record event
     * arrives with empty props and this type is the target.
     *
     * @param  callable(): array<string, mixed>  $factory
     */
    public function createUsing(callable $factory): self
    {
        $clone = clone $this;
        $clone->createUsing = $factory;

        return $clone;
    }

    public function hasCreator(): bool
    {
        return $this->createUsing !== null;
    }

    /**
     * Invoke the creator factory and return the resulting props.
     *
     * @return array<string, mixed>
     */
    public function callCreator(): array
    {
        if ($this->createUsing === null) {
            throw new \LogicException("DynamicTabType '{$this->type}': callCreator() called without a createUsing() factory registered.");
        }

        return ($this->createUsing)();
    }

    // ── Accessors ────────────────────────────────────────────────────────

    public function getType(): string
    {
        return $this->type;
    }

    public function getComponent(): string
    {
        return $this->component;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * Resolve the display label for a set of props.
     *
     * @param  array<string, mixed>  $props
     */
    public function resolveLabel(array $props): string
    {
        if ($this->labelResolver !== null) {
            return ($this->labelResolver)($props);
        }

        return ucfirst($this->type);
    }

    /**
     * Generate a stable tab ID from the type and props.
     *
     * @param  array<string, mixed>  $props
     */
    public function makeTabId(array $props): string
    {
        ksort($props);
        $hash = substr(md5(json_encode($props) ?: ''), 0, 8);

        return $this->type.'-'.$hash;
    }

    /**
     * Serialise to the array shape stored in Livewire's $openTabs state.
     *
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    public function toTabArray(array $props): array
    {
        $tabProps = $this->definitionClass !== null
            ? array_merge(['definitionClass' => $this->definitionClass], $props)
            : $props;

        return [
            'id' => $this->makeTabId($props),
            'type' => $this->type,
            'label' => $this->resolveLabel($props),
            'icon' => $this->icon,
            'component' => $this->component,
            'props' => $tabProps,
            'pinned' => false,
            'lazy' => false,
            'mounted' => true,
        ];
    }
}
