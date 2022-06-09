<?php namespace Sreynoldsjr\ReynoldsDbf\Test;

use Sreynoldsjr\ReynoldsDbf\ReynoldsDbfFacade;
use Sreynoldsjr\ReynoldsDbf\ReynoldsDbfServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    /**
     * Load package service provider
     * @param  \Illuminate\Foundation\Application $app
     * @return lasselehtinen\MyPackage\MyPackageServiceProvider
     */
    protected function getPackageProviders($app)
    {
        return [ReynoldsDbfServiceProvider::class];
    }
    /**
     * Load package alias
     * @param  \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'ReynoldsDbf' => ReynoldsDbfFacade::class,
        ];
    }
}