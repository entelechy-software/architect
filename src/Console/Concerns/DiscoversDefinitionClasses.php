<?php

declare(strict_types=1);

namespace Entelechy\Architect\Console\Concerns;

use Entelechy\Architect\Support\DefinitionClassScanner;
use Illuminate\Support\Facades\File as FileFacade;

/**
 * Shared class-discovery scanner: given a set of directories, finds every
 * concrete class declaring a given public static method, without ever
 * require()-ing/executing the file just to learn its class name.
 *
 * The actual tokenizing scan lives in Support\DefinitionClassScanner (Phase 3
 * extracted it out of here so Support\Doctor\* auditors could reuse it
 * without a Command dependency) — this trait just adds $this->warn()
 * reporting for missing paths on top, for Command subclasses.
 *
 * Intended for use inside Illuminate\Console\Command subclasses only —
 * calls $this->warn() directly.
 */
trait DiscoversDefinitionClasses
{
    /**
     * @param  list<string>  $paths
     * @return list<class-string>
     */
    protected function findClassesWithMethod(array $paths, string $method): array
    {
        $validPaths = [];

        foreach ($paths as $path) {
            if (! FileFacade::isDirectory($path)) {
                $this->warn("Discovery path does not exist, skipping: {$path}");

                continue;
            }

            $validPaths[] = $path;
        }

        return (new DefinitionClassScanner)->findClassesWithMethod($validPaths, $method);
    }
}
