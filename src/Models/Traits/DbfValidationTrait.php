<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Traits;

// When Using this Trait
// You must add a private property array "requiredAttributes" to each Model.

trait DbfValidationTrait {
  public function validate($properties){
  	
  	//checking if $properties contains all required $keys
  	foreach($this->getRequiredAttributes() AS $req){
  		if(!isset($properties[$req])){
  			$arr_diff = array_diff($this->getRequiredAttributes(), array_keys($properties));
  			throw new \ErrorException(
  				'VALIDATION FAILED. Properties do not contain all required attributes for '. get_class($this) . 
  				' missing => [' .  implode(",", $arr_diff ) . ']' .
  				' contains => [' . implode(",", array_keys($properties)) . ']'
  			);
  		}
  	}
  }

  public function getRequiredAttributes(){
  	return $this->requiredAttributes;
  }

}