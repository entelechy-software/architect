<?php

declare(strict_types=1);

namespace Entelechy\Architect\Support\Doctor;

use Entelechy\Architect\Forms\ControlRegistry;
use Entelechy\Architect\Support\Maturity;

/**
 * Shared audit logic behind both DocMaturityAuditorTest (PHPUnit
 * regression guard) and `php artisan architect:doctor` (on-demand CLI
 * report). See ARCHITECT_IMPROVEMENT_PLAN.md Phase 5.
 *
 * Every Forms control registered as anything other than Maturity::Stable
 * must carry a `<span class="maturity-badge maturity-{level}"
 * data-maturity-key="{key}">` somewhere under package/docs/_documentation
 * — this is what keeps a doc page's badge from silently going stale once
 * a control's real Maturity changes in ArchitectServiceProvider (e.g.
 * Experimental -> Stable once actually finished, or the reverse) without
 * anyone remembering to update the doc by hand.
 */
final class DocMaturityAuditor
{
    public function __construct(private readonly ControlRegistry $registry) {}

    /** @return list<string> Human-readable findings; empty when clean. */
    public function findings(): array
    {
        $docsPath = __DIR__.'/../../../docs/_documentation';

        if (! is_dir($docsPath)) {
            // Docs are a gitignored, disk-only asset — a fresh checkout/CI
            // clone without them present is expected, not a real failure.
            return [];
        }

        $findings = [];
        $badgesByKey = $this->collectBadges($docsPath);

        foreach ($this->registry->all() as $key => $control) {
            $maturity = $control->maturity();

            if ($maturity === Maturity::Stable) {
                continue;
            }

            if (! isset($badgesByKey[$key])) {
                $findings[] = "Control '{$key}' is Maturity::{$maturity->label()} but has no ".
                    'maturity badge anywhere under docs/_documentation.';

                continue;
            }

            foreach ($badgesByKey[$key] as [$file, $badgeMaturity]) {
                if ($badgeMaturity !== $maturity) {
                    $findings[] = "Doc badge for '{$key}' in {$file} says '{$badgeMaturity->label()}' but ".
                        "the registry says '{$maturity->label()}' — badge is stale.";
                }
            }
        }

        foreach ($badgesByKey as $key => $entries) {
            if (! $this->registry->has($key)) {
                foreach ($entries as [$file]) {
                    $findings[] = "Doc badge in {$file} references unknown control key '{$key}'.";
                }
            }
        }

        return $findings;
    }

    /** @return array<string, list<array{0: string, 1: Maturity}>> */
    private function collectBadges(string $docsPath): array
    {
        $badges = [];

        foreach (glob($docsPath.'/*.html') ?: [] as $file) {
            $source = (string) file_get_contents($file);

            if (! preg_match_all('/<span\s+([^>]*\bclass="[^"]*\bmaturity-badge\b[^"]*"[^>]*)>/i', $source, $spans, PREG_SET_ORDER)) {
                continue;
            }

            foreach ($spans as $span) {
                $attrs = $span[1];

                if (! preg_match('/data-maturity-key="([a-z0-9-]+)"/', $attrs, $keyMatch)) {
                    continue;
                }

                if (! preg_match('/\bmaturity-(stable|beta|experimental|planned)\b/', $attrs, $levelMatch)) {
                    continue;
                }

                $badges[$keyMatch[1]][] = [basename($file), Maturity::from($levelMatch[1])];
            }
        }

        return $badges;
    }
}
