<?php namespace Sreynoldsjr\ReynoldsDbf\Models;

use Sreynoldsjr\ReynoldsDbf\Models\ModelHeads;

class Ancientheads extends ModelHeads {

     use \Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializeHeadTrait;
     
     public $table = 'ancientheads';
}