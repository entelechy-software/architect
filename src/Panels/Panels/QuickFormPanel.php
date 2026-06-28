<?php

declare(strict_types=1);

namespace Entelechy\Architect\Panels\Panels;

use Closure;
use Entelechy\Architect\Forms\Contracts\StructureItem;
use Entelechy\Architect\Panels\ArchitectPanelDefinition;
use Entelechy\Architect\Panels\Contracts\Panel;

/**
 * Panel that embeds a standalone form rendered by FormEngine.
 *
 * Usage:
 *   QuickFormPanel::make()
 *       ->title('Quick Contact')
 *       ->structure([TextField::make('name')->required()])
 *       ->saveUsing(fn (array $data) => Contact::create($data))
 *       ->successMessage('Contact saved!')
 */
class QuickFormPanel implements Panel
{
    protected ?string $title = null;

    /** @var array<int, StructureItem> */
    protected array $structure = [];

    protected ?Closure $saveUsing = null;

    protected string $successMessage = 'Saved successfully.';

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

    /** @param array<int, StructureItem> $items */
    public function structure(array $items): static
    {
        $clone = clone $this;
        $clone->structure = $items;

        return $clone;
    }

    public function saveUsing(Closure $callback): static
    {
        $clone = clone $this;
        $clone->saveUsing = $callback;

        return $clone;
    }

    public function successMessage(string $message): static
    {
        $clone = clone $this;
        $clone->successMessage = $message;

        return $clone;
    }

    public function getType(): string
    {
        return 'quick-form';
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    /** @return array<int, StructureItem> */
    public function getStructure(): array
    {
        return $this->structure;
    }

    public function getSaveUsing(): ?Closure
    {
        return $this->saveUsing;
    }

    public function getSuccessMessage(): string
    {
        return $this->successMessage;
    }

    public function build(): ArchitectPanelDefinition
    {
        return new ArchitectPanelDefinition(
            type: $this->getType(),
            title: $this->title,
            config: [
                'structure' => $this->structure,
                'successMessage' => $this->successMessage,
            ],
            panel: $this,
        );
    }
}
