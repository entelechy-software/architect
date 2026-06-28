<?php

declare(strict_types=1);

namespace Entelechy\Architect\Actions\Actions;

use Closure;
use Entelechy\Architect\Actions\Contracts\ArchitectAction;
use Illuminate\Database\Eloquent\Model;

/**
 * Abstract base for all Architect actions.
 *
 * Implements the immutable-clone builder pattern used throughout the package.
 * Host-app action subclasses configure behaviour by overriding protected
 * properties:
 *
 *   class DeleteMemberAction extends DeleteAction
 *   {
 *       protected ?string $modelClass   = Member::class;
 *       protected string  $label        = 'Delete Member';
 *       protected string  $confirmationMessage = 'This will remove the member.';
 *   }
 *
 * Alternatively, use the fluent builder: DeleteAction::make()->modelClass(...).
 *
 * The ActionEngine instantiates actions via `new $class()` (no constructor
 * arguments), so all configuration must live in properties or be supplied
 * through the fluent API before the class name is serialised into Livewire
 * state.  For dynamic, per-request configuration extend the class and override
 * the relevant property.
 */
abstract class Action implements ArchitectAction
{
    protected string $label = '';

    protected ?string $icon = null;

    protected string $color = 'primary';

    protected bool $destructive = false;

    protected bool $confirmationRequired = false;

    protected string $confirmationTitle = 'Are you sure?';

    protected string $confirmationMessage = 'This action cannot be undone.';

    protected ?Closure $authorizationCallback = null;

    protected ?Closure $actionCallback = null;

    /** FQCN of the Eloquent model used by resolveRecord(). */
    protected ?string $modelClass = null;

    final public function __construct() {}

    public static function make(): static
    {
        return new static;
    }

    // ─── Fluent setters (immutable clone) ───────────────────────────────────

    public function label(string $label): static
    {
        $clone = clone $this;
        $clone->label = $label;

        return $clone;
    }

    public function icon(string $icon): static
    {
        $clone = clone $this;
        $clone->icon = $icon;

        return $clone;
    }

    public function color(string $color): static
    {
        $clone = clone $this;
        $clone->color = $color;

        return $clone;
    }

    public function destructive(bool $condition = true): static
    {
        $clone = clone $this;
        $clone->destructive = $condition;

        return $clone;
    }

    /**
     * Enable a confirmation dialog before the action runs.
     *
     * @param  bool|string  $title  Pass true to use the default title, or a custom title string.
     */
    public function requiresConfirmation(bool|string $title = true, string $body = ''): static
    {
        $clone = clone $this;
        $clone->confirmationRequired = true;

        if (is_string($title)) {
            $clone->confirmationTitle = $title;
        }

        if ($body !== '') {
            $clone->confirmationMessage = $body;
        }

        return $clone;
    }

    /** Closure receives the resolved record and should return bool. */
    public function authorize(Closure $callback): static
    {
        $clone = clone $this;
        $clone->authorizationCallback = $callback;

        return $clone;
    }

    /** Override the default run() logic with a custom closure. */
    public function action(Closure $callback): static
    {
        $clone = clone $this;
        $clone->actionCallback = $callback;

        return $clone;
    }

    /**
     * FQCN of the Eloquent model this action operates on.
     * Used by the default resolveRecord() implementation.
     *
     * @param  class-string<Model>  $class
     */
    public function modelClass(string $class): static
    {
        $clone = clone $this;
        $clone->modelClass = $class;

        return $clone;
    }

    // ─── ArchitectAction contract ────────────────────────────────────────────

    public function getKey(): string
    {
        return static::class;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function isDestructive(): bool
    {
        return $this->destructive;
    }

    public function isConfirmationRequired(): bool
    {
        return $this->confirmationRequired;
    }

    public function getConfirmationTitle(): string
    {
        return $this->confirmationTitle;
    }

    public function getConfirmationMessage(): string
    {
        return $this->confirmationMessage;
    }

    public function canRun(mixed $record): bool
    {
        if ($this->authorizationCallback !== null) {
            return (bool) ($this->authorizationCallback)($record);
        }

        return true;
    }

    /** @param array<string, mixed> $data */
    public function run(mixed $record, array $data = []): void
    {
        if ($this->actionCallback !== null) {
            ($this->actionCallback)($record, $data);
        }
    }

    public function resolveRecord(int $id): mixed
    {
        if ($this->modelClass !== null) {
            /** @var class-string<Model> $class */
            $class = $this->modelClass;

            return $class::findOrFail($id);
        }

        return null;
    }

    // ─── Protected helpers ───────────────────────────────────────────────────

    /**
     * Assert the resolved record is an Eloquent model.
     *
     * @throws \InvalidArgumentException When the record is not a Model instance.
     */
    protected function getModelOrFail(mixed $record): Model
    {
        if (! $record instanceof Model) {
            throw new \InvalidArgumentException(
                static::class.' requires an Eloquent Model instance. Got: '.gettype($record).'.'
            );
        }

        return $record;
    }
}
