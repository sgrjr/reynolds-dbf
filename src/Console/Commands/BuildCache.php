<?php namespace Sreynoldsjr\ReynoldsDbf\Console\Commands;

use Illuminate\Console\Command;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Vendors;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Inventories;

class BuildCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rdbf:cache {table?} {method?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build User Related Cache.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        ini_set('memory_limit','512M');
        
        $this->comment(PHP_EOL."Building Cache " . "...".PHP_EOL);
        
        if(!$this->argument('table')){
            $this->comment(PHP_EOL."Password Cache Everything ...".PHP_EOL);
            Vendors::buildCache();
            $this->comment(PHP_EOL."Inventories Cache Everything ...".PHP_EOL);
            Inventories::buildCache();
        }else{
            switch($this->argument('table')){
                case 'vendors':
                    Vendors::buildCache($this->argument('method'));
                    break;
                case 'inventories':
                    Inventories::buildCache($this->argument('method'));
            }
        }
        
        $this->comment(PHP_EOL."Cache complete.".PHP_EOL);
    }
}
