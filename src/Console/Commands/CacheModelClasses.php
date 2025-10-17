<?php

namespace Lyre\Strings\Console\Commands;

use Illuminate\Console\Command;

/**
 * Command to cache model classes.
 * 
 * @package Lyre\Strings\Console\Commands
 */
class CacheModelClasses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'strings:cache-models';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache all model classes for better performance';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Caching model classes...');

        // Clear existing cache
        cache()->forget('app_model_classes');

        // Regenerate cache
        app(\Lyre\Strings\Services\Model\ModelService::class)->getModelClasses();

        $this->info('Model classes cached successfully!');

        return 0;
    }
}
