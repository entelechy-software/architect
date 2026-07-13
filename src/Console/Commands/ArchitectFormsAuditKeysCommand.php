<?php

declare(strict_types=1);

namespace Entelechy\Architect\Console\Commands;

use Entelechy\Architect\Console\Concerns\DiscoversDefinitionClasses;
use Entelechy\Architect\Forms\ArchitectFormDefinition;
use Entelechy\Architect\Forms\ArchitectWizardDefinition;
use Illuminate\Console\Command;
use Throwable;

/**
 * Project-wide form/wizard key collision detector — complements
 * Forms\FormKeyRegistry's same-request runtime detection with an offline
 * scan across every definition class in the project, intended for CI.
 *
 * See FORMS_API_COMPATIBILITY_CONTRACT.md, "Form key uniqueness contract".
 */
class ArchitectFormsAuditKeysCommand extends Command
{
    use DiscoversDefinitionClasses;

    protected $signature = 'architect:forms:audit-keys {--path=* : Directories to scan (defaults to config(\'architect.forms.discovery.paths\'), falling back to app_path())}';

    protected $description = 'Scan form/wizard definition classes for duplicate form keys across the whole project.';

    public function handle(): int
    {
        /** @var list<string> $optionPaths */
        $optionPaths = array_values((array) $this->option('path'));

        $paths = $optionPaths !== []
            ? $optionPaths
            : array_values((array) config('architect.forms.discovery.paths', [app_path()]));

        $classes = $this->findClassesWithMethod($paths, 'definition');

        /** @var array<string, list<class-string>> $byKey */
        $byKey = [];

        foreach ($classes as $class) {
            try {
                $definition = $class::definition();
            } catch (Throwable $e) {
                $this->warn("Skipping {$class}: definition() threw ".$e->getMessage());

                continue;
            }

            if (! $definition instanceof ArchitectFormDefinition && ! $definition instanceof ArchitectWizardDefinition) {
                continue;
            }

            $byKey[$definition->key][] = $class;
        }

        /** @var array<string, list<class-string>> $duplicates */
        $duplicates = array_filter($byKey, fn (array $owners): bool => count($owners) > 1);

        if ($duplicates === []) {
            $this->info('No duplicate form keys found across '.count($classes).' definition class(es).');

            return self::SUCCESS;
        }

        $this->error('Duplicate form keys found:');

        foreach ($duplicates as $key => $owners) {
            $this->line("  '{$key}' used by: ".implode(', ', $owners));
        }

        return self::FAILURE;
    }
}
