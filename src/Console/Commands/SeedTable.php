<?php namespace Sreynoldsjr\ReynoldsDbf\Console\Commands;

use Illuminate\Console\Command;

class SeedTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rdbf:seed {table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed Named Table in Database from related DBF.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->comment(PHP_EOL."Seeding Database Table " . $this->argument('table') . "...".PHP_EOL);
        '\\Sreynoldsjr\\ReynoldsDbf\\Models\\Eloquent\\'. ucfirst($this->argument('table'))::seedTable();
        $this->comment(PHP_EOL."Seeding complete.".PHP_EOL);
    }
}
