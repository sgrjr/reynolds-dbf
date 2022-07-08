<?php namespace Sreynoldsjr\ReynoldsDbf\Console\Commands;

use Illuminate\Console\Command;
use Sreynoldsjr\ReynoldsDbf\ReynoldsDbf;

class CreateTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rdbf:create {table?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates Named Table in Database from related DBF.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if(!$this->argument('table')) return ReynoldsDbf::install();

        $this->comment(PHP_EOL."Creating Database Table ".$this->argument('table')."...".PHP_EOL);
                $class_name = '\Sreynoldsjr\ReynoldsDbf\Models\Eloquent\\'  . ucfirst($this->argument('table'));
        (new $class_name)->migrate();
    }
}
