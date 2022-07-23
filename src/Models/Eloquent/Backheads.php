<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent;

use Illuminate\Database\Eloquent\SoftDeletes;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\HeadTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Backdetails;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Interfaces\ModelInterface;

class Backheads extends BaseModel implements ModelInterface {

	use HeadTrait, SoftDeletes, \Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializeHeadTrait;

	protected $table = "backheads";
	protected $appends = ['freeShipping'];
  public $migration = "2022_00_00_06_backheads.php";
	protected $dbfPrimaryKey = 'TRANSNO';
	  protected $seed = [
    'dbf_backheads'
  ];
  protected $indexes = ["TRANSNO", "KEY"];
  protected $attributeTypes = [ 
    "_config"=>"backheads",
  ];	
    protected $with = [];
    protected $withCount = ['items'];
  public function items(){
    return $this->hasMany(Backdetails::class,'TRANSNO','TRANSNO');
  }
}
