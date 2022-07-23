<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Traits;

use Config;
use Sreynoldsjr\ReynoldsDbf\Helpers\DataEntry;

trait SetDefaultsTrait {

    public function setIfNotSet($key, $val, $force = false, $func_arg = false){

        if($force || !isset($this->attributes[$key])){

            if(method_exists($this,$val)){
                $this->$key = $this->transformToDataEntry($key, $this->$val($func_arg));
            }else{
                $this->$key = $this->transformToDataEntry($key, $val);
            }
        }

        return $this;
    }

    public function transformToDataEntry($col, $value){
        if(!is_string($value) && $value != null) return $value;
        $de = DataEntry::make($value, $this->database()->getColumnByName($col));
        return $de;
    }

}