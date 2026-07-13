<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Exceptions;

use RuntimeException;

/**
 * Thrown by Forms\FormKeyRegistry when two different definition classes
 * attempt to register the same form/wizard key within the same request.
 * See FORMS_API_COMPATIBILITY_CONTRACT.md, "Form key uniqueness contract".
 */
final class DuplicateFormKeyException extends RuntimeException {}
