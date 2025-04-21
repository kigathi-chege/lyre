<?php

namespace Lyre\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CacheModelRelationships extends Command
{
    protected $signature = 'cache:relationships';
    protected $description = 'Clears and recaches model relationship mappings';

    public function handle()
    {
        $models = get_model_classes();

        $this->info('Clearing old relationship caches...');

        foreach ($models as $modelName => $modelClass) {
            if ($modelName == 'User') {
                continue;
            }
            $this->info("Clearing {$modelName} cache...");
            $cacheKey = 'model_relationships_' . $modelName;
            Cache::forget($cacheKey);
            $this->comment("Cleared {$modelName} cache.");
            try {
                $this->comment("Attempting to cache: {$modelName}...");
                $relationships = $modelClass::getModelRelationships();
            } catch (\Throwable $th) {
                $this->error("Failed to cache: {$modelName} (" . $th->getMessage() . ")");
            }
            $this->alert("Cached: {$modelName} (" . count($relationships) . " relations)");
        }

        $this->info('✅ Relationships cached successfully!');
    }
}
