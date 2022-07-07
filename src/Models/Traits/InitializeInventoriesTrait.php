<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Traits;

use Config;

trait InitializeInventoriesTrait {

public function initialize(){

     if($this->ISBN == null){
        $message = 'Model MUST be given a KEY. given: ' . json_encode($this->getAttributes());
        throw new \ErrorException($message);
     }

    return $this;
  }
		
}