<?php namespace Sreynoldsjr\ReynoldsDbf\Console\Commands;

use Illuminate\Console\Command;

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
    protected $description = 'Creates and Seeds Database Tables from DBFs.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->comment(PHP_EOL."JUST A TEST".PHP_EOL);
    }
}
