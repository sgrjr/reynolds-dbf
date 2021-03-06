<?php namespace Sreynoldsjr\ReynoldsDbf\Console\Commands;

use Illuminate\Console\Command;
use Sreynoldsjr\ReynoldsDbf\ReynoldsDbf;
use Illuminate\Support\Facades\Artisan;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Passwords;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Vendors;

class RebuildTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rdbf:rebuild {table?}';

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
        if(!$this->argument('table')) return ReynoldsDbf::rebuild();

        $this->comment(PHP_EOL."Rebuilding database Table ".$this->argument('table')."...".PHP_EOL);

        if($this->argument('table') === 'users') return $this->users();

        $class_name = '\Sreynoldsjr\ReynoldsDbf\Models\Eloquent\\'  . ucfirst($this->argument('table'));
        (new $class_name)->rebuildTable();
    }

    public function users(){
        
        $this->comment(PHP_EOL."Rebuilding Vendors table...".PHP_EOL);
        (new Vendors)->rebuildTable();
        
        $this->comment(PHP_EOL."Rebuilding Passwords table...".PHP_EOL);
        (new Passwords)->rebuildTable();
        
        $this->comment(PHP_EOL."Rebuilding Teams...".PHP_EOL);
        Artisan::call('teams:seed --reset');
    }
}
