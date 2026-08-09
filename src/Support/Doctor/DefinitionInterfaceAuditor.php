<?php

declare(strict_types=1);

namespace Entelechy\Architect\Support\Doctor;

use Entelechy\Architect\Content\Contracts\ProvidesContentDefinition;
use Entelechy\Architect\Forms\Contracts\ProvidesFormDefinition;
use Entelechy\Architect\Forms\Contracts\ProvidesWizardDefinition;
use Entelechy\Architect\Navigator\Contracts\ProvidesNavigatorDefinition;
use Entelechy\Architect\Panels\Contracts\ProvidesDashboardDefinition;
use Entelechy\Architect\Stats\Contracts\ProvidesStatDefinition;
use Entelechy\Architect\Supersearch\Contracts\ProvidesSupersearchDefinition;
use Entelechy\Architect\Support\DefinitionClassScanner;
use Entelechy\Architect\Table\Contracts\ProvidesTableDefinition;
use Entelechy\Architect\Toolbar\Contracts\ProvidesToolbarDefinition;

/**
 * Shared audit logic behind both DefinitionInterfaceAuditorTest (PHPUnit
 * regression guard) and `php artisan architect:doctor` (on-demand CLI
 * report). See ARCHITECT_IMPROVEMENT_PLAN.md Phase 3.2.
 *
 * Scans host-app classes (via the same safe, side-effect-free
 * Support\DefinitionClassScanner used by architect:forms:audit-keys and
 * architect:storage:discover) for a static definition() or build() method,
 * and reports any that don't implement one of the per-subsystem
 * Provides*Definition marker interfaces — i.e. a class engines can no
 * longer resolve via instanceof/is_subclass_of, only the old
 * method_exists() duck-typing this phase removed.
 */
final class DefinitionInterfaceAuditor
{
    /** @var list<class-string> */
    private const MARKER_INTERFACES = [
        ProvidesTableDefinition::class,
        ProvidesContentDefinition::class,
        ProvidesStatDefinition::class,
        ProvidesDashboardDefinition::class,
        ProvidesFormDefinition::class,
        ProvidesWizardDefinition::class,
        ProvidesToolbarDefinition::class,
        ProvidesSupersearchDefinition::class,
        ProvidesNavigatorDefinition::class,
    ];

    public function __construct(private readonly DefinitionClassScanner $scanner = new DefinitionClassScanner) {}

    /** @return list<string> Human-readable findings; empty when clean. */
    public function findings(): array
    {
        $configured = array_values((array) config('architect.doctor.discovery.paths', []));
        $paths = $configured !== [] ? $configured : [app_path()];

        $classes = array_values(array_unique(array_merge(
            $this->scanner->findClassesWithMethod($paths, 'definition'),
            $this->scanner->findClassesWithMethod($paths, 'build'),
        )));

        $findings = [];

        foreach ($classes as $class) {
            if ($this->implementsAMarkerInterface($class)) {
                continue;
            }

            $method = method_exists($class, 'definition') ? 'definition' : 'build';

            $findings[] = "Class '{$class}' exposes a static {$method}() method but does not implement any ".
                'Provides*Definition marker interface (Entelechy\\Architect\\{Subsystem}\\Contracts\\Provides*Definition) — '.
                'engines can no longer resolve it via method_exists() duck-typing. See ARCHITECT_IMPROVEMENT_PLAN.md Phase 3.';
        }

        return $findings;
    }

    /** @param  class-string  $class */
    private function implementsAMarkerInterface(string $class): bool
    {
        foreach (self::MARKER_INTERFACES as $interface) {
            if (is_subclass_of($class, $interface)) {
                return true;
            }
        }

        return false;
    }
}
