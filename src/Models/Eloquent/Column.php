<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent;

use stdclass;

class Column {
	public function __construct(Array $atts){
		$this->attributes = $atts;
	}

	public function __get($att){
        if(isset($this->attributes[$att])){
            return $this->attributes[$att];
        }

        $method = 'get'. ucfirst(strtolower($att)) . 'Attribute';
        return $this->$method();
    }

    public function __call($name, $arguments){
    	
    	if(isset($this->attributes[$name])){
    		return $this->attributes[$name];
    	}
    	dump('line 24 of Eloquent\Columns.php');
    	var_dump($name);
    	var_dump($arguments);
    	throw($this);
    }
}
