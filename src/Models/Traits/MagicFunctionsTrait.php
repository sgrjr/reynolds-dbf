<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Traits;

//This trait depends on the model also having the HasAttributesTrait

trait MagicFunctionsTrait {

  /**
 * Dynamically retrieve attributes on the model.
 *
 * @param  string  $key
 * @return mixed
 */
public function __get($key)
{
    return $this->getAttribute($key);
}

/**
 * Dynamically set attributes on the model.
 *
 * @param  string  $key
 * @param  mixed  $value
 * @return void
 */
public function __set($key, $value)
{
    $this->setAttribute($key, $value);
}

}