<?php namespace Sreynoldsjr\ReynoldsDbf\Console\Commands;

use Illuminate\Console\Command;
use Sreynoldsjr\ReynoldsDbf\ReynoldsDbf;

class EmptyTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rdbf:empty {table?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Empty Named Table in Database.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if(!$this->argument('table')) return ReynoldsDbf::empty();

        $this->comment(PHP_EOL."Emptying database Table ".$this->argument('table')."...".PHP_EOL);
        $class_name = '\Sreynoldsjr\ReynoldsDbf\Models\Eloquent\\'  . ucfirst($this->argument('table'));
        (new $class_name)->emptyTable();
    }
}
