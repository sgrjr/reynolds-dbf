<?php namespace Sreynoldsjr\ReynoldsDbf\Models;

use Sreynoldsjr\ReynoldsDbf\Models\Model;

class Inventories extends Model {

     use \Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializeInventoriesTrait;

     public $table = 'inventories';
}