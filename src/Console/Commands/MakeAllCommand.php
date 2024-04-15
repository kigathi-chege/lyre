<?php

namespace Lyre\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MakeAllCommand extends Command
{
    protected $signature = 'make:all
                            {model : The name of the model}';

    protected $description = 'This command creates a model with all related classes.';

    public function handle()
    {
        $arguments = $this->arguments();
        $modelName = $arguments['model'];
        $this->info("Publishing stubs...");
        Artisan::call('lyre:stubs');
        Artisan::call('make:model', ['name' => $modelName, '--all' => true, '--api' => true]);
        $this->info("Created a new eloquent model class.");
        Artisan::call('make:resource', ['name' => $modelName]);
        $this->info("Created a new resource.");
        Artisan::call('make:repository', ['repository' => $modelName]);
        $this->info("Created class repository.");
    }
}
