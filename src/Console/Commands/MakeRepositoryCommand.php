<?php

namespace Lyre\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Lyre\Traits\RepositoryTrait;

class MakeRepositoryCommand extends Command implements PromptsForMissingInput
{
    use RepositoryTrait;

    protected $signature = 'lyre:repository
                            {repository : The name of the repository}';

    protected $description = 'This command generates a repository class';

    public function handle()
    {
        $arguments = $this->arguments();
        $repositoryName = $arguments['repository'];
        $this->createRepositoryInterface($repositoryName);
        $this->createRepositoryClass($repositoryName);
        $this->info("Repository '$repositoryName' created successfully!");
    }

    protected function promptForMissingArgumentsUsing()
    {
        return [
            'repository' => 'Which repository should be created?',
        ];
    }
}
