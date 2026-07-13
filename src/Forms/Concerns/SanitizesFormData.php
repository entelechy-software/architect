<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Concerns;

use Entelechy\Architect\Contracts\PermissionResolver;
use Entelechy\Architect\Forms\Contracts\StructureItem;

/**
 * Server-side anti-tamper enforcement for form submission (Phase 1 security
 * boundaries — see FORMS_FEATURE_PLAN.md Architectural Principle "Security
 * is server authoritative").
 *
 * Client-submitted formData is never trusted for fields that the server
 * considers hidden, disabled, or permission-gated at the moment of
 * submission — a user cannot use devtools to un-hide/un-disable a field
 * and smuggle a value through:
 *
 * - Conditionally hidden fields (->hidden() evaluates true): the submitted
 *   value is dropped entirely. A hidden field's data is not meaningful in
 *   that state.
 * - Conditionally disabled fields, and fields gated by ->permission() the
 *   current user lacks: the submitted value is reverted to whatever the
 *   field held immediately after mount (from fill()/defaults), not simply
 *   dropped — these are typically legitimate persisted values the field
 *   just shouldn't have been editable for this state/user.
 *
 * Requires the consuming class to also `use FlattensStructure`.
 */
trait SanitizesFormData
{
    /**
     * @param  array<int, StructureItem>  $structure
     * @param  array<string, mixed>  $formData
     * @param  array<string, mixed>  $originalFormData  Snapshot captured right after mount(), before any user interaction.
     * @return array<string, mixed>
     */
    protected function sanitizeAgainstFields(array $structure, array $formData, array $originalFormData): array
    {
        $get = fn (string $f): mixed => data_get($formData, $f);
        $resolver = app(PermissionResolver::class);
        $user = auth()->user();

        foreach ($this->flattenFields($structure) as $field) {
            $name = $field->getName();

            if ($field->isHidden($get)) {
                unset($formData[$name]);

                continue;
            }

            $blockedByDisabled = $field->isDisabled($get);
            $blockedByPermission = method_exists($field, 'getPermission')
                && $field->getPermission() !== null
                && ! $resolver->can($user, $field->getPermission());

            if ($blockedByDisabled || $blockedByPermission) {
                if (array_key_exists($name, $originalFormData)) {
                    $formData[$name] = $originalFormData[$name];
                } else {
                    unset($formData[$name]);
                }
            }
        }

        return $formData;
    }
}
