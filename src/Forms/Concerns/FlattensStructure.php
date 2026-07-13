<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Concerns;

use Entelechy\Architect\Forms\Contracts\ArchitectField;
use Entelechy\Architect\Forms\Contracts\StructureItem;

/**
 * Recursively flattens Section/Grid/Fieldset (and any other container
 * exposing getStructure()) into their leaf ArchitectField items.
 *
 * Extracted in Phase 1 from what were previously two byte-for-byte
 * identical private methods on FormEngine and WizardEngine — a pure
 * internal refactor, no behavior change and no public API impact (see
 * FORMS_API_COMPATIBILITY_CONTRACT.md, "what is explicitly allowed to
 * change").
 */
trait FlattensStructure
{
    /**
     * @param  array<int, StructureItem>  $structure
     * @return array<int, ArchitectField>
     */
    protected function flattenFields(array $structure): array
    {
        $fields = [];

        foreach ($structure as $item) {
            if ($item instanceof ArchitectField) {
                $fields[] = $item;

                continue;
            }

            if (method_exists($item, 'getStructure')) {
                $fields = array_merge($fields, $this->flattenFields($item->getStructure()));
            }
        }

        return $fields;
    }
}
