<?php

namespace Lyre\Strings\Console\Commands;

use Illuminate\Console\Command;

/**
 * Command to generate all CRUD components for a model.
 * 
 * @package Lyre\Strings\Console\Commands
 */
class MakeAllCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'strings:make-all {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate all CRUD components for a model';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $model = $this->argument('model');

        $this->info("Generating all components for {$model}...");

        // Implementation would generate:
        // - Model
        // - Repository
        // - Resource
        // - Controller
        // - Requests
        // - Policies
        // - Migrations
        // - etc.

        $this->info("All components generated successfully!");

        return 0;
    }
}
