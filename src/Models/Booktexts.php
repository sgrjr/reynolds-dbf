<?php namespace Sreynoldsjr\ReynoldsDbf\Models;

use Sreynoldsjr\ReynoldsDbf\Models\Model;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializeBooktextsTrait;

class Booktexts extends Model {

     use InitializeBooktextsTrait;
     
     public $table = 'booktexts';
}