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
    $att = $this->getAttribute($key);

    if(is_string($att)){
        return $att;
    }else if($att !== null){
        if(is_object($att) && isset($att->value)) return $att->value;
        return $att;
    }else{
        $name = "get" . ucfirst($key). "Attribute";
        return $this->$name();
    }
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
    if(is_string($value)) $this->setAttribute($key, $this->transformToDataEntry($key,$value));
    $this->setAttribute($key, $value);
}

}