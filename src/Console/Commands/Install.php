<?php namespace Sreynoldsjr\ReynoldsDbf\Console\Commands;

use Illuminate\Console\Command;
use \Sreynoldsjr\ReynoldsDbf\ReynoldsDbf;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rdbf:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create all Tables from related DBF.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->comment(PHP_EOL."Creating All Database Tables from DBFS...".PHP_EOL);
        ReynoldsDbf::install();
        $this->comment(PHP_EOL."Creating all tables complete.".PHP_EOL);
    }
}
