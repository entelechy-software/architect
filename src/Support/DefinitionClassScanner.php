<?php

declare(strict_types=1);

namespace Entelechy\Architect\Support;

use Illuminate\Support\Facades\File as FileFacade;

/**
 * Safe, side-effect-free class discovery: given a set of directories, finds
 * every concrete class declaring a given public static method, without ever
 * require()-ing/executing the file just to learn its class name (tokenizes
 * instead).
 *
 * Extracted from Console\Concerns\DiscoversDefinitionClasses (Phase 3) so
 * Support\Doctor\* auditors — which must stay Command-independent, usable
 * from both a PHPUnit test and `architect:doctor` — can reuse the exact
 * same scanning logic without any console-output side effects. That trait
 * now delegates here and adds its own $this->warn() reporting on top.
 */
final class DefinitionClassScanner
{
    /**
     * Silently skips paths that don't exist — callers wanting to warn about
     * a missing path (e.g. a Command) should check isDirectory() themselves.
     *
     * @param  list<string>  $paths
     * @return list<class-string>
     */
    public function findClassesWithMethod(array $paths, string $method): array
    {
        $classes = [];

        foreach ($paths as $path) {
            if (! FileFacade::isDirectory($path)) {
                continue;
            }

            foreach (FileFacade::allFiles($path) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $fqcn = $this->extractFqcnFromFile($file->getPathname());

                if ($fqcn === null || ! class_exists($fqcn) || ! method_exists($fqcn, $method)) {
                    continue;
                }

                $classes[] = $fqcn;
            }
        }

        return $classes;
    }

    /**
     * Extracts the fully-qualified class name declared in a PHP file via
     * tokenizing rather than require\/include — discovery must never execute
     * arbitrary host-app file side effects just to learn a class name.
     */
    public function extractFqcnFromFile(string $path): ?string
    {
        $contents = FileFacade::get($path);
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

    /**
     * @param  list<mixed>  $tokens
     */
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
