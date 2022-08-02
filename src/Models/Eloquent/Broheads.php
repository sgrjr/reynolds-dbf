<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent;

use \Illuminate\Database\Eloquent\SoftDeletes;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Interfaces\ModelInterface;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\HeadTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Brodetails;

class Broheads extends BaseModel implements ModelInterface {
	
	use HeadTrait, SoftDeletes, \Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializeHeadTrait;
    
	protected $appends = ['freeShipping','items_count'];
	protected $table = "broheads";	
	public $migration = "2022_00_00_08_broheads.php";
	protected $dbfPrimaryKey = 'TRANSNO';
	protected $seed = [
    	'dbf_broheads'
  	];
  	 protected $indexes = ["TRANSNO", "KEY"];

      protected $attributeTypes = [ 
        "_config"=>"broheads",
      ];
    protected $with = [];
    protected $withCount = ['items'];

  public function items(){
    return $this->hasMany(Brodetails::class,'TRANSNO','TRANSNO');
  }
}
