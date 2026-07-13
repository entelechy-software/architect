<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Contracts;

use Entelechy\Architect\Forms\Fields\Field;

/**
 * Describes a control (form field type) registered in the Forms control
 * registry (Entelechy\Architect\Forms\ControlRegistry, Phase 3).
 *
 * This is an internal seam introduced in Phase 1 so the registry built in
 * Phase 3 has a stable shape to target — it does not change how any
 * existing Field subclass behaves, and no existing Field is required to
 * implement it directly (the registry stores this metadata alongside the
 * field class, not on the field class itself).
 */
interface ControlMetadata
{
    /** Unique registry key, e.g. 'text', 'currency', 'map-location'. */
    public function key(): string;

    /** @return class-string<Field> */
    public function fieldClass(): string;

    /** Human-readable category used for docs grouping, e.g. 'Numeric', 'Visual & Spatial'. */
    public function category(): string;

    /**
     * The underlying value type this control produces, e.g. 'string',
     * 'integer', 'decimal', 'array', 'date'. Informational — used for docs
     * and for Phase 4's validation-default inference, not enforced here.
     */
    public function valueType(): string;

    /** Short one-line description for the control catalog docs. */
    public function description(): string;
}
