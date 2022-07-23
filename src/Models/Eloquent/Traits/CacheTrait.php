<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits;

use Config;
 
trait CacheTrait {

     public static function buildCache($method = false){
          if(!$method) return static::cacheEverything();
          $method = 'cache' . ucfirst($method);
          return static:: $method();
     }

     public static function getCache($name){
        $method = 'getCache' . ucfirst($name);
        return (new static)->$method();
     }      
		
}