
<?php namespace Sreynoldsjr\ReynoldsDbf\Console\Commands;

use Illuminate\Console\Command;
use \Sreynoldsjr\ReynoldsDbf\ReynoldsDbf;
use Artisan;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rdbf:install {--seed} {--force} {--teams} {--cache}';

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
        
        // Build the tables
        ReynoldsDbf::install($this->option('force'));
        
        // NUCLEAR FLAG
        $force = $this->option('force')? ' --reset':'';
        
        // Seed the Tables
        if($this->option('seed')) ReynoldsDbf::seed($this->option('force'));

        if($this->option('teams')) Artisan::call('teams:seed --reset' . $force);
        //Cache
        if($this->option('cache')){
            Artisan::call('rdbf:cache');
        } 

        $this->comment(PHP_EOL."Creating all tables complete.".PHP_EOL);
    }
}
