<?php

namespace Lyre\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MakeAllCommand extends Command
{
    protected $signature = 'lyre:truncate
                            {model : The name of the model}';

    protected $description = "This command truncates a model's table";

    public function handle()
    {
        $arguments = $this->arguments();
        $modelName = $arguments['model'];
        $modelClass = config('lyre.model-path') . $modelName;

        if (!class_exists($modelClass)) {
            $this->error("Model class {$modelClass} does not exist.");
            return 1;
        }

        $modelInstance = new $modelClass;

        try {
            DB::table($modelInstance->getTable())->truncate();
            $this->info("Table for model {$modelName} has been truncated successfully.");
        } catch (\Exception $e) {
            $this->error("Failed to truncate the table: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
