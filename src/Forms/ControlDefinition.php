<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms;

use Entelechy\Architect\Forms\Contracts\ControlMetadata;
use Entelechy\Architect\Forms\Fields\Field;
use Entelechy\Architect\Support\Maturity;

/**
 * Immutable ControlMetadata value object. Created via ControlRegistry's
 * register() helpers rather than constructed directly by host apps.
 */
final class ControlDefinition implements ControlMetadata
{
    /**
     * @param  class-string<Field>  $fieldClass
     */
    public function __construct(
        private readonly string $key,
        private readonly string $fieldClass,
        private readonly string $category,
        private readonly string $valueType,
        private readonly string $description,
        private readonly Maturity $maturity,
    ) {}

    public function key(): string
    {
        return $this->key;
    }

    public function fieldClass(): string
    {
        return $this->fieldClass;
    }

    public function category(): string
    {
        return $this->category;
    }

    public function valueType(): string
    {
        return $this->valueType;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function maturity(): Maturity
    {
        return $this->maturity;
    }
}
