<?php

declare(strict_types=1);

namespace Entelechy\Architect\Support\Doctor;

use Entelechy\Architect\Forms\ControlRegistry;
use Entelechy\Architect\Forms\Fields\Field;
use Entelechy\Architect\Support\Maturity;

/**
 * Shared audit logic behind both ControlMaturityAuditTest (PHPUnit
 * regression guard) and `php artisan architect:doctor` (on-demand CLI
 * report). See ARCHITECT_IMPROVEMENT_PLAN.md Phase 0.
 *
 * Finds every Forms control registered as Maturity::Stable whose Blade
 * view references a named Alpine component (`x-data="architectXyz(...)"`)
 * that has no matching `Alpine.data('architectXyz', ...)` registration
 * anywhere under resources/js — i.e. a control that claims to work but
 * is provably wired to nothing.
 */
final class ControlMaturityAuditor
{
    public function __construct(private readonly ControlRegistry $registry) {}

    /** @return list<string> Human-readable findings; empty when clean. */
    public function findings(): array
    {
        $registeredAlpineComponents = $this->registeredAlpineComponentNames();
        $findings = [];

        foreach ($this->registry->byMaturity(Maturity::Stable) as $key => $control) {
            $fieldClass = $control->fieldClass();
            /** @var Field $field */
            $field = $fieldClass::make('doctor_probe');

            if ($field->getViewName() === '') {
                continue;
            }

            $viewPath = $this->resolveViewPath($field->getViewName());

            if ($viewPath === null || ! file_exists($viewPath)) {
                continue;
            }

            $viewSource = (string) file_get_contents($viewPath);

            if (! preg_match('/x-data="(architect[A-Za-z]+)\(/', $viewSource, $matches)) {
                continue;
            }

            $alpineComponentName = $matches[1];

            if (! in_array($alpineComponentName, $registeredAlpineComponents, true)) {
                $findings[] = "Control '{$key}' ({$fieldClass}) is Maturity::Stable but its view references ".
                    "the unregistered Alpine component '{$alpineComponentName}'.";
            }
        }

        return $findings;
    }

    /** @return list<string> */
    private function registeredAlpineComponentNames(): array
    {
        $names = [];

        foreach ($this->jsFiles() as $path) {
            $source = (string) file_get_contents($path);

            if (preg_match_all('/Alpine\.data\(\s*[\'"]([a-zA-Z0-9_-]+)[\'"]/', $source, $matches)) {
                array_push($names, ...$matches[1]);
            }
        }

        return $names;
    }

    /** @return list<string> */
    private function jsFiles(): array
    {
        $jsDir = __DIR__.'/../../../resources/js';
        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($jsDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'js') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function resolveViewPath(string $viewName): ?string
    {
        if (! str_starts_with($viewName, 'architect::')) {
            return null;
        }

        $relative = str_replace('.', '/', substr($viewName, strlen('architect::'))).'.blade.php';

        return __DIR__.'/../../../resources/views/'.$relative;
    }
}
