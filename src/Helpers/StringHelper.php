<?php namespace Sreynoldsjr\ReynoldsDbf\Helpers;

class StringHelper {
	
	public static function camelCase($string){
		//$string = strtolower($string);
    	if(str_contains($string,"_")){
    		$array = explode('_',$string); 
    		$k = '';
    		foreach($array AS $key=>$val){
    			if($key === 0){
    				$k .= $val;
    			}else{
    				$k .= ucfirst($val);
    			}
    		}
    		$string = $k;
    	}

    	return $string;
	}
}