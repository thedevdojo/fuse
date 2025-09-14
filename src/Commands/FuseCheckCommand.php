<?php

namespace Devdojo\Fuse\Commands;

use Illuminate\Console\Command;
use Devdojo\Fuse\Checkers\WireBindingChecker;

class FuseCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'fuse:check';

    /**
     * The console command description.
     */
    protected $description = 'Run Fuse static analysis checks on your Livewire components';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Running Fuse static analysis checks...');
        $this->line('');

        $checker = new WireBindingChecker();
        $results = $checker->check();

        $this->displayResults($results);

        return 0;
    }

    /**
     * Display the check results.
     */
    private function displayResults($results)
    {
        if (empty($results['errors'])) {
            $this->info('✅ All $wire binding checks passed!');
            return;
        }

        $this->error('❌ Found ' . count($results['errors']) . ' $wire binding issue(s):');
        $this->line('');

        foreach ($results['errors'] as $error) {
            $this->line("<fg=red>• {$error['type']}: {$error['message']}</>");
            $this->line("  File: {$error['file']}:{$error['line']}");
            $this->line('');
        }
    }
}