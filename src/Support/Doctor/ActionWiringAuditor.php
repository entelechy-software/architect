<?php

declare(strict_types=1);

namespace Entelechy\Architect\Support\Doctor;

/**
 * Shared audit logic behind both ActionWiringAuditTest (PHPUnit
 * regression guard) and `php artisan architect:doctor` (on-demand CLI
 * report). See ARCHITECT_IMPROVEMENT_PLAN.md Phase 0.
 *
 * Guards against the "half-wired feature" bug shape found repeatedly in
 * this package — a built-in row/bulk action that Table\Livewire\Engine
 * dispatches as a plain browser event with no client-side listener at
 * all (a silent no-op, e.g. the original ->clonable()/->viewable() bugs).
 * A listener that only shows a "not yet available" toast still counts as
 * wired (an honest stub, matching the ->auditable() precedent) — this
 * only catches events with *no* listener whatsoever.
 */
final class ActionWiringAuditor
{
    /**
     * Every browser event Table\Livewire\Engine can dispatch for a
     * built-in action with no direct server-side handle() call. Add new
     * entries here whenever a new client-dispatch-only built-in action
     * is added to Engine.php.
     *
     * @var list<string>
     */
    private const CLIENT_DISPATCH_EVENTS = [
        'row-action:audit',
        'row-action:view',
        'architect:export',
        'architect:copy',
        'architect:bulk-email',
        'architect:bulk-status',
    ];

    /** @return list<string> Human-readable findings; empty when clean. */
    public function findings(): array
    {
        $jsSource = $this->concatenateJsSource();
        $findings = [];

        foreach (self::CLIENT_DISPATCH_EVENTS as $event) {
            if (! preg_match('/Livewire\.on\(\s*[\'"]'.preg_quote($event, '/').'[\'"]/', $jsSource)) {
                $findings[] = "No Livewire.on('{$event}') listener found under resources/js — this action ".
                    'currently does nothing when triggered.';
            }
        }

        return $findings;
    }

    private function concatenateJsSource(): string
    {
        $jsDir = __DIR__.'/../../../resources/js';
        $source = '';

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($jsDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'js') {
                $source .= file_get_contents($file->getPathname())."\n";
            }
        }

        return $source;
    }
}
