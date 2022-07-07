<?php namespace Sreynoldsjr\ReynoldsDbf\Console\Commands;

use Illuminate\Console\Command;

class DropTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rdbf:drop {table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drops Named Table in Database.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->comment(PHP_EOL."Dropping Database Table ".$this->argument('table')."...".PHP_EOL);
        '\\Sreynoldsjr\\ReynoldsDbf\\Models\\Eloquent\\'. ucfirst($this->argument('table'))::dropTable();
    }
}
