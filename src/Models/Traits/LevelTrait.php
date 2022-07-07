<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Traits;

Trait LevelTrait
{  public function getLevelAttribute(){
    switch($this->SERIES){
        case "CLASS_H":
            return "LEVEL 1";
        case "CLASS_I":
            return "LEVEL 2";
        case "CLASS_J":
            return "LEVEL 3";
        default:
            return null;
    }
    
  }
}
