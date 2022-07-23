<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Traits;

use Config;

trait InitializePassfilesTrait {

public function initialize(){

    /* if($this->KEY == null){
        $message = 'Model MUST be given a KEY. given: ' . json_encode($this->getAttributes());
        throw new \ErrorException($message);
     }
   */
    return $this;
  }
		
}