<?php

namespace Lyre\Console\Commands;

use Illuminate\Console\Command;

class CacheModelClasses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:model-classes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used to clear model classes cache and re-cache model classes for faster access.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Clearing old model classes cache...');

        // Clear the cache for model classes
        cache()->forget('app_model_classes');

        $defaultNamespaces = config('lyre.path.model', ['App\\Models']);

        foreach ($defaultNamespaces as $namespace) {
            logger("Clearing old model classes cache for namespace: {$namespace}...");
            cache()->forget("app_model_classes:{$namespace}");
        }

        $this->comment('Old model classes cache cleared.');

        // Re-cache the model classes
        $this->info('Re-caching model classes...');

        get_model_classes();

        $this->info('Model classes re-cached successfully!');
    }
}
