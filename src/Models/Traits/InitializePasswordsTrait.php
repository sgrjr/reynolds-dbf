<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Traits;

use Config, Hash;

trait InitializePasswordsTrait {

public function initialize(){

    /* if($this->KEY == null){
        $message = 'Model MUST be given a KEY. given: ' . json_encode($this->getAttributes());
        throw new \ErrorException($message);
     }

      if(!isset($this->attributes['password']) && isset($this->attributes['UPASS']) ){
         $this->attributes['password'] = Hash::make($this->attributes['UPASS']);
      }
   */
    return $this;
  }
		
}