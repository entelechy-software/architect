<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Validation;

use Illuminate\Support\Facades\Validator;

/**
 * Default Laravel-native pass-through implementation of ValidationPipeline.
 *
 * Delegates entirely to Illuminate\Validation\Validator — identical
 * behavior to the raw Validator::make() calls already used throughout
 * FormEngine/WizardEngine. This is the "keep Laravel-native rules as
 * first-class" half of the Phase 4 validation strategy; the DSL layer
 * built in Phase 4 compiles down to the same rule arrays this class
 * consumes, so native rules and DSL-generated rules are always run through
 * the exact same code path.
 */
final class NativeValidationPipeline implements ValidationPipeline
{
    public function validate(array $rules, array $data): array
    {
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            /** @var array<string, array<int, string>> $errors */
            $errors = $validator->errors()->toArray();

            return $errors;
        }

        return [];
    }
}
