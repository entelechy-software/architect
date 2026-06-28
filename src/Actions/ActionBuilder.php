<?php

declare(strict_types=1);

namespace Entelechy\Architect\Actions;

use Entelechy\Architect\Actions\Contracts\ArchitectAction;

/**
 * Fluent builder for an ordered set of actions.
 *
 * Usage:
 *   Architect::action('member-row-actions')
 *       ->add(EditMemberAction::class)
 *       ->add(DeleteMemberAction::class)
 *       ->build();
 */
final class ActionBuilder
{
    /** @var array<int, class-string<ArchitectAction>> */
    private array $actionClasses = [];

    private function __construct(private string $key) {}

    public static function make(string $key): static
    {
        return new self($key);
    }

    /** @param class-string<ArchitectAction> $actionClass */
    public function add(string $actionClass): static
    {
        $this->actionClasses[] = $actionClass;

        return $this;
    }

    public function build(): ArchitectActionDefinition
    {
        return new ArchitectActionDefinition(
            key: $this->key,
            actionClasses: $this->actionClasses,
        );
    }
}
