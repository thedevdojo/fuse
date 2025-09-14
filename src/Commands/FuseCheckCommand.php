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
    protected $description = 'Validate $wire bindings in Livewire components and Blade templates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Running $wire binding validation...');
        $this->line('Checking all Livewire components and their associated Blade templates...');
        $this->line('');

        $checker = new WireBindingChecker();
        $results = $checker->check();

        $this->displayResults($results);

        // Return appropriate exit code
        return empty($results['errors']) ? 0 : 1;
    }

    /**
     * Display the check results.
     */
    private function displayResults($results)
    {
        if (empty($results['errors'])) {
            $this->info('✅ All $wire binding checks passed!');
            $this->line('');
            $this->line('No issues found with your Livewire component bindings.');
            return;
        }

        $this->error('❌ Found ' . count($results['errors']) . ' $wire binding issue(s):');
        $this->line('');

        foreach ($results['errors'] as $error) {
            $this->line("<fg=red>• {$error['type']}: {$error['message']}</>");
            $this->line("  <fg=yellow>File: {$error['file']}:{$error['line']}</>");
            $this->line('');
        }
        
        $this->line('<fg=cyan>Fix these issues to ensure your Livewire components work correctly in production.</>');
    }
}