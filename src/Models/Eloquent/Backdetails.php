<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent;

use Illuminate\Database\Eloquent\SoftDeletes;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\DetailTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\LevelTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Interfaces\ModelInterface;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Models\Backheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Models\Vendors;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Models\Inventories;

class Backdetails extends BaseModel implements ModelInterface {

	use DetailTrait, LevelTrait, SoftDeletes, \Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializeDetailTrait;

	protected $table = "backdetails";
    public $migration = "2022_00_00_07_backdetails.php";
	  protected $seed = [
        'dbf_backdetails'
      ];
        protected $attributeTypes = [ 
        "_config"=>"backdetails",
      ];

      protected $indexes = ["KEY"];

    public $foreignKeys = [
        ["TRANSNO","TRANSNO","backheads"], //TRANSNO references TRANSNO on backheads
        ["PROD_NO","ISBN","inventories"], //PROD_NO references ISBN on inventories
    ];


	public function head()
    {
        return $this->belongsTo(Backhead::class,'TRANSNO','TRANSNO');
    }
	
	public function vendor()
    {
        return $this->belongsTo(Vendor::class,'KEY','KEY');
    }
	
	public function book()
    {
        return $this->belongsTo(Inventories::class,'PROD_NO','ISBN');
    }
}
