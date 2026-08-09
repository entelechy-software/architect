<?php

declare(strict_types=1);

namespace Entelechy\Architect\Support\Doctor;

use Entelechy\Architect\Table\AbstractEloquentDataModel;
use ReflectionMethod;

/**
 * Shared audit logic behind both TenantScopeAuditTest (PHPUnit regression
 * guard) and `php artisan architect:doctor` (on-demand CLI report). See
 * ARCHITECT_IMPROVEMENT_PLAN.md Phase 4.
 *
 * AbstractEloquentDataModel::baseQuery() applies the current TenantContext
 * — row-level scoping (HasTenantScope) and/or connection switching — but a
 * subclass overriding baseQuery() without calling parent::baseQuery() first
 * silently opts that model out of both, with no error at any point. This
 * scans configured host-app directories for exactly that mistake.
 *
 * Requires config('architect.tenant.discovery.paths') to be set explicitly;
 * empty (the default) skips the check rather than failing, since most
 * projects using this package are single-tenant.
 */
final class TenantScopeAuditor
{
    /** @return list<string> Human-readable findings; empty when clean. */
    public function findings(): array
    {
        $paths = array_values((array) config('architect.tenant.discovery.paths', []));
        $findings = [];

        foreach ($this->findDataModelClasses($paths) as $dataModelClass) {
            $method = new ReflectionMethod($dataModelClass, 'baseQuery');

            if ($method->getDeclaringClass()->getName() === AbstractEloquentDataModel::class) {
                continue;
            }

            if (! $this->callsParentBaseQuery($method)) {
                $findings[] = "{$dataModelClass}::baseQuery() overrides the base implementation without ".
                    'calling parent::baseQuery() first — tenant scoping and connection switching '.
                    '(see ARCHITECT_IMPROVEMENT_PLAN.md Phase 4) are silently bypassed for this model.';
            }
        }

        return $findings;
    }

    private function callsParentBaseQuery(ReflectionMethod $method): bool
    {
        $file = $method->getFileName();
        $start = $method->getStartLine();
        $end = $method->getEndLine();

        if ($file === false || $start === false || $end === false) {
            return true; // can't inspect the source — don't false-positive.
        }

        $lines = file($file);

        if ($lines === false) {
            return true;
        }

        $body = implode('', array_slice($lines, $start - 1, $end - $start + 1));

        return str_contains($body, 'parent::baseQuery(');
    }

    /**
     * @param  list<string>  $paths
     * @return list<class-string<AbstractEloquentDataModel>>
     */
    private function findDataModelClasses(array $paths): array
    {
        $classes = [];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $fqcn = $this->extractFqcnFromFile($file->getPathname());

                if ($fqcn === null || ! class_exists($fqcn) || ! is_subclass_of($fqcn, AbstractEloquentDataModel::class)) {
                    continue;
                }

                /** @var class-string<AbstractEloquentDataModel> $fqcn */
                $classes[] = $fqcn;
            }
        }

        return array_values(array_unique($classes));
    }

    /**
     * Extracts the fully-qualified class name declared in a PHP file via
     * tokenizing rather than require\/include — discovery must never execute
     * arbitrary host-app file side effects just to learn a class name.
     */
    private function extractFqcnFromFile(string $path): ?string
    {
        $contents = (string) file_get_contents($path);
        $tokens = token_get_all($contents);
        $count = count($tokens);

        $namespace = '';
        $class = null;

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            if (is_array($token) && $token[0] === T_NAMESPACE) {
                $namespace = '';
                for ($j = $i + 1; $j < $count; $j++) {
                    if ($tokens[$j] === ';' || $tokens[$j] === '{') {
                        break;
                    }
                    if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)) {
                        $namespace .= $tokens[$j][1];
                    }
                }
            }

            if (is_array($token) && $token[0] === T_CLASS) {
                $previous = $this->previousSignificantToken($tokens, $i);
                if (is_array($previous) && $previous[0] === T_DOUBLE_COLON) {
                    // `Foo::class` constant usage, not a class declaration.
                    continue;
                }

                for ($j = $i + 1; $j < $count; $j++) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                        $class = $tokens[$j][1];
                        break 2;
                    }
                }
            }
        }

        if ($class === null) {
            return null;
        }

        return $namespace !== '' ? $namespace.'\\'.$class : $class;
    }

    /** @param  list<mixed>  $tokens */
    private function previousSignificantToken(array $tokens, int $index): mixed
    {
        for ($k = $index - 1; $k >= 0; $k--) {
            if (is_array($tokens[$k]) && $tokens[$k][0] === T_WHITESPACE) {
                continue;
            }

            return $tokens[$k];
        }

        return null;
    }
}
