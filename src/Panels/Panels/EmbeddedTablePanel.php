<?php

declare(strict_types=1);

namespace Entelechy\Architect\Panels\Panels;

use Entelechy\Architect\Panels\ArchitectPanelDefinition;
use Entelechy\Architect\Panels\Contracts\Panel;
use Illuminate\Database\Eloquent\Model;

/**
 * Panel that embeds a full Architect table (via its TableDefinition class).
 *
 * Usage:
 *   EmbeddedTablePanel::make()
 *       ->title('Recent Orders')
 *       ->definition(RecentOrdersTableDefinition::class)
 *       ->parentRecord($activity, 'activity_id')
 */
class EmbeddedTablePanel implements Panel
{
    protected ?string $title = null;

    /** @var class-string|null */
    protected ?string $definitionClass = null;

    protected ?Model $parentRecord = null;

    protected ?string $parentForeignKey = null;

    final public function __construct() {}

    public static function make(): static
    {
        return new static;
    }

    public function title(string $title): static
    {
        $clone = clone $this;
        $clone->title = $title;

        return $clone;
    }

    /** @param class-string $class FQCN of the host-app TableDefinition class. */
    public function definition(string $class): static
    {
        $clone = clone $this;
        $clone->definitionClass = $class;

        return $clone;
    }

    /**
     * Scope the embedded table to rows belonging to a parent record, e.g.
     * an activity's committees. Mirrors Engine::mount()'s $scope parameter
     * (QueryContext::$scope) — enables managing related records inline.
     */
    public function parentRecord(?Model $record, ?string $foreignKey = null): static
    {
        $clone = clone $this;
        $clone->parentRecord = $record;
        $clone->parentForeignKey = $foreignKey;

        return $clone;
    }

    public function getType(): string
    {
        return 'embedded-table';
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDefinitionClass(): ?string
    {
        return $this->definitionClass;
    }

    /** @return array<string, int|string> */
    public function getScope(): array
    {
        if ($this->parentRecord === null || $this->parentForeignKey === null) {
            return [];
        }

        return [$this->parentForeignKey => $this->parentRecord->getKey()];
    }

    public function build(): ArchitectPanelDefinition
    {
        return new ArchitectPanelDefinition(
            type: $this->getType(),
            title: $this->title,
            config: [
                'definitionClass' => $this->definitionClass,
                'scope' => $this->getScope(),
            ],
            panel: $this,
        );
    }
}
