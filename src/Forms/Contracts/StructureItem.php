<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Contracts;

/**
 * Marker interface for anything that can appear in a form's structure
 * array: fields, sections, grids, and grouping-only items like Fieldset.
 *
 * The FormEngine and structure-item partial dispatch on concrete type,
 * not on this interface's methods — it exists purely to type the
 * `structure(array)` parameter on FormBuilder, Section, Grid, etc.
 */
interface StructureItem {}
