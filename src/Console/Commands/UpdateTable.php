<?php namespace Sreynoldsjr\ReynoldsDbf\Console\Commands;

use Illuminate\Console\Command;
use Sreynoldsjr\ReynoldsDbf\ReynoldsDbf;

class UpdateTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rdbf:update {table?}';

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
        if(!$this->argument('table')) return ReynoldsDbf::update();

        $this->comment(PHP_EOL."Updating Database Table " . $this->argument('table') . "...".PHP_EOL);
        $class_name = '\Sreynoldsjr\ReynoldsDbf\Models\Eloquent\\'  . ucfirst($this->argument('table'));
        (new $class_name)->updateTable();
        $this->comment(PHP_EOL."Updating complete.".PHP_EOL);
    }
}
