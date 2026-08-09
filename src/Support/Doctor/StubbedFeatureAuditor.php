<?php

declare(strict_types=1);

namespace Entelechy\Architect\Support\Doctor;

/**
 * Shared audit logic behind both StubbedFeatureAuditTest (PHPUnit
 * regression guard) and `php artisan architect:doctor` (on-demand CLI
 * report). See ARCHITECT_IMPROVEMENT_PLAN.md Phase 2.
 *
 * Phase 2's audit found several builder methods that are real, validated,
 * and threaded through to their definition object, but have no consumer
 * anywhere in the package (e.g. ToolbarBuilder::bind(), NavigatorBuilder::
 * position()) or whose consumer is itself an acknowledged no-op (e.g.
 * ToolbarBadge::live(), which ToolbarEngine never resolves). Rather than
 * leaving these as silent gaps, each was given an explicit "KNOWN GAP
 * (tracked, not yet wired)" disclosure in its own docblock.
 *
 * This auditor is the mechanical allowlist the plan calls for: it makes
 * sure that disclosure text is never silently deleted (e.g. by a refactor
 * that assumes the method "must" already work because it reads fine) without
 * either the gap being genuinely fixed (which should also remove the
 * matching entry below) or the disclosure being intentionally kept.
 */
final class StubbedFeatureAuditor
{
    /**
     * Every currently-tracked stub. Each entry is [relative src path,
     * marker substring expected somewhere in that file's docblock].
     * Remove an entry once the gap is genuinely fixed; add a new one
     * whenever a builder method is documented as a known-not-wired stub
     * instead of being fixed outright.
     *
     * @var list<array{path: string, marker: string}>
     */
    private const TRACKED_STUBS = [
        [
            'path' => 'src/Toolbar/Items/ToolbarBadge.php',
            'marker' => 'KNOWN GAP (tracked, not yet wired): unlike MetricCard::live()',
        ],
        [
            'path' => 'src/Toolbar/ToolbarBuilder.php',
            'marker' => 'KNOWN GAP (tracked, not yet wired): ToolbarEngine dispatches',
        ],
        [
            'path' => 'src/Navigator/NavigatorBuilder.php',
            'marker' => 'KNOWN GAP (tracked, not yet wired): this value is validated',
        ],
        [
            'path' => 'src/Stats/StatBuilder.php',
            'marker' => 'KNOWN GAP (tracked, not yet wired): building a non-\'dashboard\' type',
        ],
    ];

    /** @return list<string> Human-readable findings; empty when clean. */
    public function findings(): array
    {
        $findings = [];

        foreach (self::TRACKED_STUBS as $stub) {
            $fullPath = __DIR__.'/../../../'.$stub['path'];

            if (! is_file($fullPath)) {
                $findings[] = "Tracked stub file missing: {$stub['path']} (was it moved or deleted without ".
                    'updating StubbedFeatureAuditor::TRACKED_STUBS?)';

                continue;
            }

            $source = file_get_contents($fullPath);

            if ($source === false) {
                $findings[] = "Could not read tracked stub file: {$stub['path']}";

                continue;
            }

            if (! str_contains($source, $stub['marker'])) {
                $findings[] = "Expected known-gap disclosure missing from {$stub['path']} — either the gap was ".
                    'silently fixed (remove this entry) or the disclosure comment was accidentally removed '.
                    '(restore it).';
            }
        }

        return $findings;
    }
}
