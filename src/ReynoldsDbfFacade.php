<?php namespace Sreynoldsjr\ReynoldsDbf;

use Illuminate\Support\Facades\Facade;

class ReynoldsDbfFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'reynolds-dbf';
    }
}