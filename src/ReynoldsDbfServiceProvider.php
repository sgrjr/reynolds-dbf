<?php namespace Sreynoldsjr\ReynoldsDbf;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Sreynoldsjr\ReynoldsDbf\View\Components\FooterComponent;
use Sreynoldsjr\ReynoldsDbf\Console\Commands\Install;
use Sreynoldsjr\ReynoldsDbf\Console\Commands\CreateTable;
use Sreynoldsjr\ReynoldsDbf\Console\Commands\DropTable;
use Sreynoldsjr\ReynoldsDbf\Console\Commands\EmptyTable;
use Sreynoldsjr\ReynoldsDbf\Console\Commands\SeedTable;
use Sreynoldsjr\ReynoldsDbf\Console\Commands\UpdateTable;
use Sreynoldsjr\ReynoldsDbf\Console\Commands\BuildUserCache;
use Sreynoldsjr\ReynoldsDbf\ReynoldsDbf;

class ReynoldsDbfServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/reynolds-dbf.php' => config_path('reynolds-dbf.php'),
        ]);

         $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

         $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

         $this->loadViewsFrom(__DIR__.'/../resources/views', 'reynolds-dbf');

         $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/reynolds-dbf'),
        ]);

         //Must Manually enter Components here...
         Blade::componentNamespace('Sreynoldsjr\\ReynoldsDbf\\Views\\Components', 'rdbf');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Install::class,
                CreateTable::class,
                DropTable::class,
                EmptyTable::class,
                UpdateTable::class,
                SeedTable::class,
                BuildUserCache::class
            ]);
        }
    }
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {

        $this->mergeConfigFrom(
            __DIR__.'/../config/reynolds-dbf.php', 'reynolds-dbf'
        );

        $this->app->singleton(ReynoldsDbf::class, function () {
            return new ReynoldsDbf();
        });
        
        $this->app->alias(ReynoldsDbf::class, 'reynolds-dbf');
    }
}