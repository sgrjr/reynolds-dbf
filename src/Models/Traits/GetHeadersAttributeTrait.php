<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Traits;

trait GetHeadersAttributeTrait {
	public function getHeadersAttribute(){
		$fillable = $this->getFillable();
        $headers = $this->getAttributeTypes();
        
        if(isset($headers["_config"])) unset($headers["_config"]); 
            
            $cols = $this->dbf()->database()->getMeta();
           
            foreach($cols AS $col){
                $con = $col->toArray();
                $name = $con["name"];

                if($name !== null ){
			    	$headers[$name] = $con;
            	}
		    }

        if(isset($headers["timestamps"]) && $headers["timestamps"] === true){
            unset($headers['timestamps']);

            $headers["created_at"] = [
            "name" => "created_at",
            "type" => "TIMESTAMP",
            "mysql_type" => "TIMESTAMP",
            "length" => 19,
            "nullable"=>false,
            "decimal_count" => 0
           ];
            $headers["updated_at"] = [
            "name" => "updated_at",
            "type" => "TIMESTAMP",
            "mysql_type" => "TIMESTAMP",
            "length" => 19,
            "nullable"=>false,
            "decimal_count" => 0
            ];
        }
            
        foreach($fillable AS $att){

            if(!isset($headers[$att])){
		    	$headers[$att] = [
                    "name" => $att,
                    "type" => "String",
                    "mysql_type" => "String",
                    "length" => 96,
                    "nullable"=> true,
            		"decimal_count" => 0
                ];
			}
            
		}

	    return $headers;
	}
}