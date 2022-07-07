<?php namespace Sreynoldsjr\ReynoldsDbf\Models;

use Sreynoldsjr\ReynoldsDbf\Models\Model;
use Sreynoldsjr\ReynoldsDbf\Models\ModelHeads;

class Backheads extends ModelHeads {

     use \Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializeHeadTrait;

     public $table = 'backheads';
}