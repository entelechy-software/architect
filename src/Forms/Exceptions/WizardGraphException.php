<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Exceptions;

use RuntimeException;

/**
 * Thrown by WizardBuilder::build() when a wizard's step graph is invalid:
 * duplicate step ids, a branch() referencing an unknown step id or field,
 * a then() target that doesn't exist, or a step unreachable from the first
 * step. Always thrown before the wizard ever reaches runtime — see
 * FORMS_FEATURE_PLAN.md Phase 2, "pre-runtime wizard graph validation".
 */
final class WizardGraphException extends RuntimeException {}
