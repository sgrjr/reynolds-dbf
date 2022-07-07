<?php namespace Sreynoldsjr\ReynoldsDbf\Console\Commands;

use Illuminate\Console\Command;

class UpdateTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rdbf:update {table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Named Table in Database from related DBF by comparing changes.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->comment(PHP_EOL."Seeding Database Table " . $this->argument('table') . "...".PHP_EOL);
        dd('\\Sreynoldsjr\\ReynoldsDbf\\Models\\Eloquent\\'. ucfirst($this->argument('table'))::static);
        '\\Sreynoldsjr\\ReynoldsDbf\\Models\\Eloquent\\'. ucfirst($this->argument('table'))::updateTable();
        $this->comment(PHP_EOL."Seeding complete.".PHP_EOL);
    }
}
