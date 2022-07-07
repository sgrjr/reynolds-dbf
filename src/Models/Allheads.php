<?php namespace Sreynoldsjr\ReynoldsDbf\Models;

use Sreynoldsjr\ReynoldsDbf\Models\ModelHeads;

class Allheads extends ModelHeads {
     use \Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializeHeadTrait;
     public $table = 'allheads';
}