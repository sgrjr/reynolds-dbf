<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Traits;

use Sreynoldsjr\ReynoldsDbf\ReynoldsDbf;

trait DbfModelTrait {

    public function dbf(){
        return ReynoldsDbf::model($this->getTable());
    }

    
		
}