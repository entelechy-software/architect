<?php

declare(strict_types=1);

namespace Entelechy\Architect\Console\Commands;

use Illuminate\Console\Command;

class ArchitectSetupStatusCommand extends Command
{
    protected $signature = 'architect:setup:status';

    protected $description = 'Show current Architect setup state and lock configuration.';

    public function handle(): int
    {
        /** @var array<string, mixed> $setup */
        $setup = (array) config('architect.setup', []);
        $chosen = (array) ($setup['chosen'] ?? []);
        $locks = (array) ($setup['locks'] ?? []);

        $this->info('Architect setup status');
        $this->line('  initialized : '.(((bool) ($setup['initialized'] ?? false)) ? 'yes' : 'no'));
        $this->line('  version     : '.(string) ($setup['version'] ?? 'n/a'));
        $this->newLine();

        $this->info('Chosen values');
        $this->line('  persistence_mode : '.(string) ($chosen['persistence_mode'] ?? 'n/a'));
        $this->line('  tenancy_mode     : '.(string) ($chosen['tenancy_mode'] ?? 'n/a'));
        $this->line('  state_table      : '.(string) ($chosen['state_table'] ?? 'n/a'));
        $this->line('  state_connection : '.(string) (($chosen['state_connection'] ?? null) ?: 'default'));
        $this->line('  auth_guard       : '.(string) ($chosen['auth_guard'] ?? config('architect.auth_guard', 'web')));
        $this->newLine();

        $this->info('Lock classes');
        $this->line('  hard : '.implode(', ', array_values((array) ($locks['hard'] ?? []))));
        $this->line('  soft : '.implode(', ', array_values((array) ($locks['soft'] ?? []))));

        return self::SUCCESS;
    }
}
