<?php namespace Sreynoldsjr\ReynoldsDbf\Models;

use Sreynoldsjr\ReynoldsDbf\Models\Model;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializeStanding_ordersTrait;

class Standing_orders extends Model {

     use InitializeStanding_ordersTrait;
     
     public $table = 'standing_orders';
}