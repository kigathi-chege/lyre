<?php

namespace Lyre\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishStubsCommand extends Command
{
    protected $signature = 'lyre:stubs';

    protected $description = 'Publish custom stubs from lyre';

    public function handle()
    {
        // $sourcePath = base_path('vendor/kigathi/lyre/stubs');
        $sourcePath = base_path('packages/kigathi/lyre/src/stubs');
        $destinationPath = base_path('stubs');

        if (File::exists($destinationPath)) {
            $this->info('Stubs already published, skipping...');
            return;
        }

        File::makeDirectory($destinationPath, 0755, true);

        File::copyDirectory($sourcePath, $destinationPath);

        $this->info('Custom stubs published successfully.');
    }
}
