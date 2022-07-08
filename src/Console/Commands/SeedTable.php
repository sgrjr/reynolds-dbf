<?php namespace Sreynoldsjr\ReynoldsDbf\Console\Commands;

use Illuminate\Console\Command;
use Sreynoldsjr\ReynoldsDbf\ReynoldsDbf;

class SeedTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rdbf:seed {table?} {--force}';

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
        if(!$this->argument('table')) ReynoldsDbf::seed($this->option('force'));

        $this->comment(PHP_EOL."Seeding Database Table " . $this->argument('table') . "...".PHP_EOL);

        $class_name = '\Sreynoldsjr\ReynoldsDbf\Models\Eloquent\\'  . ucfirst($this->argument('table'));
        $result = (new $class_name)->seedTable($this->option('force'));
        if(!$result){
            $this->comment(PHP_EOL."Nothing seeded.".PHP_EOL);
        }else{
            $this->comment(PHP_EOL."Seeding complete.".PHP_EOL);
        }
    }
}
