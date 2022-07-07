<?php namespace Sreynoldsjr\ReynoldsDbf\Models;

use Sreynoldsjr\ReynoldsDbf\Models\Model;
use Sreynoldsjr\ReynoldsDbf\Models\Vendors;

class ModelHeads extends Model {

     public function getVendorAttribute(){
          return Vendors::query()->asObject()->where('KEY','==', $this->KEY)->first();
     }

}