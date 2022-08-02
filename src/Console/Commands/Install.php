<?php namespace Sreynoldsjr\ReynoldsDbf\Console\Commands;

use Illuminate\Console\Command;
use \Sreynoldsjr\ReynoldsDbf\ReynoldsDbf;
use Illuminate\Support\Facades\Artisan;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rdbf:install {--seed} {--force} {--teams} {--cache} {--fresh}';

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

        if($this->option('fresh')) return $this->freshInstall();

        $this->comment(PHP_EOL."Creating All Database Tables from DBFS...".PHP_EOL);
            
        // Build the tables
        ReynoldsDbf::install($this->option('force'));
        
        // NUCLEAR FLAG
        $force = $this->option('force')? ' --reset':'';
        
        // Seed the Tables
        if($this->option('seed')) ReynoldsDbf::seed($this->option('force'));

        if($this->option('teams')) Artisan::call('teams:seed --reset');
        //Cache
        if($this->option('cache')){
            Artisan::call('rdbf:cache');
        } 

        $this->comment(PHP_EOL."Creating all tables complete.".PHP_EOL);
    }

    public function freshInstall(){

        $this->comment(PHP_EOL."Beginning Fresh install of aplication data.".PHP_EOL);

        $this->comment(PHP_EOL."-->1. migrate all tables.".PHP_EOL);
        ReynoldsDbf::install(true);

        $this->comment(PHP_EOL."-->2. seed those tables.".PHP_EOL);
        ReynoldsDbf::seed(true);

        $this->comment(PHP_EOL."-->3. seed teams.".PHP_EOL);
        Artisan::call('teams:seed --reset');

        $this->comment(PHP_EOL."-->4. cache everything.".PHP_EOL);
        Artisan::call('rdbf:cache');

        $this->comment(PHP_EOL."Fresh install of aplication data completed :).".PHP_EOL);
    }
}
