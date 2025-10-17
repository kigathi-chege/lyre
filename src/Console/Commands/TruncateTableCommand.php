<?php

namespace Lyre\Strings\Console\Commands;

use Illuminate\Console\Command;

/**
 * Command to truncate database tables.
 * 
 * @package Lyre\Strings\Console\Commands
 */
class TruncateTableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'strings:truncate {table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate a database table';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $table = $this->argument('table');

        if ($this->confirm("Are you sure you want to truncate the {$table} table?")) {
            \Illuminate\Support\Facades\DB::table($table)->truncate();
            $this->info("Table {$table} truncated successfully!");
        } else {
            $this->info('Operation cancelled.');
        }

        return 0;
    }
}
