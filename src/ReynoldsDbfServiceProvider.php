<?php namespace Sreynoldsjr\ReynoldsDbf;

use Illuminate\Support\ServiceProvider;

class ReynoldsDbfServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
    }
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ReynoldsDbf::class, function () {
            return new ReynoldsDbf();
        });
        $this->app->alias(ReynoldsDbf::class, 'reynolds-dbf');
    }
}