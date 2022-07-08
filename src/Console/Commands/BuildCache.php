<?php namespace Sreynoldsjr\ReynoldsDbf\Console\Commands;

use Illuminate\Console\Command;
use Sreynoldsjr\ReynoldsDbf\Models\Passwords;

class BuildCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rdbf:cache {method?}';

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
        $this->comment(PHP_EOL."Building Cache " . "...".PHP_EOL);
        Passwords::buildCache($this->argument('method'));
        $this->comment(PHP_EOL."Cache complete.".PHP_EOL);
    }
}
