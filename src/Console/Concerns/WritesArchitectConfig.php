<?php

declare(strict_types=1);

namespace Entelechy\Architect\Console\Concerns;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

/**
 * Shared atomic-write-then-lint helper for Architect setup commands that
 * rewrite `config/architect.php` (ArchitectInitCommand, ArchitectStorageInitCommand).
 */
trait WritesArchitectConfig
{
    /**
     * Writes the config file atomically: render to a temp file, validate the
     * PHP syntax of the rendered file, back up the previous config, then
     * atomically replace it. Aborts without touching the real file if the
     * rendered output does not parse.
     *
     * @param  array<string, mixed>  $config
     */
    private function writeConfig(Filesystem $files, string $configPath, array $config): void
    {
        $content = "<?php\n\nreturn ".$this->exportArray($config, 0).";\n";

        $tmpPath = $configPath.'.tmp-'.uniqid();
        $files->put($tmpPath, $content);

        $lintOutput = [];
        $exitCode = 0;
        exec('php -l '.escapeshellarg($tmpPath).' 2>&1', $lintOutput, $exitCode);

        if ($exitCode !== 0) {
            $files->delete($tmpPath);

            throw new RuntimeException(
                "Refusing to write config/architect.php: rendered output failed php -l:\n".implode("\n", $lintOutput)
            );
        }

        if ($files->exists($configPath)) {
            $backupPath = $configPath.'.bak-'.now()->format('Y_m_d_His');

            // Guard against rapid successive re-inits within the same second,
            // which would otherwise silently overwrite the previous backup.
            if ($files->exists($backupPath)) {
                $backupPath .= '-'.substr(uniqid('', true), -6);
            }

            $files->copy($configPath, $backupPath);
        }

        $files->move($tmpPath, $configPath);
    }

    /**
     * Pretty-prints a config array as PHP using short array syntax, since
     * var_export() emits the legacy array(...) form and produces noisy diffs.
     */
    private function exportArray(mixed $value, int $depth): string
    {
        if (is_array($value)) {
            $indent = str_repeat('    ', $depth + 1);
            $closingIndent = str_repeat('    ', $depth);
            $isList = array_is_list($value);

            $lines = [];
            foreach ($value as $key => $item) {
                $rendered = $this->exportArray($item, $depth + 1);
                $lines[] = $isList
                    ? "{$indent}{$rendered},"
                    : "{$indent}".var_export($key, true)." => {$rendered},";
            }

            if ($lines === []) {
                return '[]';
            }

            return "[\n".implode("\n", $lines)."\n{$closingIndent}]";
        }

        return var_export($value, true);
    }
}
