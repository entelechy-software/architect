<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms;

use Closure;
use Entelechy\Architect\Forms\Contracts\StructureItem;

/**
 * Fluent builder for a multi-step Architect wizard form.
 *
 * Usage:
 *   Architect::wizard('onboarding')
 *       ->step('Personal Details', [
 *           TextField::make('name')->required(),
 *           EmailField::make('email')->required(),
 *       ])
 *       ->step('Preferences', [
 *           SelectField::make('role')->options([...]),
 *       ])
 *       ->saveUsing(fn (array $data) => User::create($data))
 *       ->completedRoute('/dashboard')
 *       ->build();
 */
final class WizardBuilder
{
    /** @var array<int, array{label: string, structure: array<int, StructureItem>}> */
    private array $steps = [];

    private ?Closure $saveUsing = null;

    private ?string $cancelRoute = null;

    private ?string $completedRoute = null;

    private function __construct(private string $key) {}

    public static function make(string $key): static
    {
        return new self($key);
    }

    /** @param array<int, StructureItem> $structure */
    public function step(string $label, array $structure): static
    {
        $this->steps[] = ['label' => $label, 'structure' => $structure];

        return $this;
    }

    public function saveUsing(Closure $callback): static
    {
        $this->saveUsing = $callback;

        return $this;
    }

    public function cancelRoute(string $route): static
    {
        $this->cancelRoute = $route;

        return $this;
    }

    public function completedRoute(string $route): static
    {
        $this->completedRoute = $route;

        return $this;
    }

    public function build(): ArchitectWizardDefinition
    {
        return new ArchitectWizardDefinition(
            key: $this->key,
            steps: $this->steps,
            saveUsing: $this->saveUsing,
            cancelRoute: $this->cancelRoute,
            completedRoute: $this->completedRoute,
        );
    }
}
