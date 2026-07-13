<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Validation;

/**
 * Internal seam introduced in Phase 1 so Phase 4's validation DSL and
 * default-profile registry have a stable interface to compile into, without
 * requiring any change to how FormEngine/WizardEngine validate today.
 *
 * Phase 1 ships exactly one implementation, NativeValidationPipeline, which
 * is a pure pass-through to Laravel's own validator — behavior identical to
 * today's Validator::make() usage. Nothing in FormEngine/WizardEngine's
 * actual submit()/autosave()/nextStep() flow is rewired to use this yet;
 * that wiring happens in Phase 4 once the DSL/default-profile layer exists
 * to compile into rules this pipeline consumes.
 */
interface ValidationPipeline
{
    /**
     * @param  array<string, array<int, string>|string>  $rules  Laravel-style validation rules, keyed by field name.
     * @param  array<string, mixed>  $data
     * @return array<string, array<int, string>> Validation error messages keyed by field name; empty array means valid.
     */
    public function validate(array $rules, array $data): array;
}
