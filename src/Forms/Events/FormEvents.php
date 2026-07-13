<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Events;

/**
 * Browser event names dispatched by FormEngine/WizardEngine. Centralized
 * so every dispatch site and every listener/test refers to the same
 * constant rather than a repeated string literal.
 *
 * Event names themselves are part of the frozen public contract (see
 * FORMS_API_COMPATIBILITY_CONTRACT.md) — only new constants are added here,
 * existing ones are never renamed.
 */
final class FormEvents
{
    public const SAVED = 'architect:form:saved';

    public const AUTOSAVED = 'architect:form:autosaved';

    public const WIZARD_STEP_ENTERED = 'architect:wizard:step-entered';

    public const WIZARD_STEP_LEAVING = 'architect:wizard:step-leaving';

    public const WIZARD_STEP_VALIDATED = 'architect:wizard:step-validated';

    public const WIZARD_COMPLETED = 'architect:wizard:completed';

    public const WIZARD_DRAFT_SAVED = 'architect:wizard:draft-saved';

    public const WIZARD_NAVIGATION_BLOCKED = 'architect:wizard:navigation-blocked';

    private function __construct() {}
}
