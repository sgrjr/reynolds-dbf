<?php namespace Sreynoldsjr\ReynoldsDbf\Models;

use Sreynoldsjr\ReynoldsDbf\Models\Model;

class Passfiles extends Model {

     use \Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializePassfilesTrait;

     public $table = 'passfiles';
}