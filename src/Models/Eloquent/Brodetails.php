<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent;

use Illuminate\Database\Eloquent\SoftDeletes;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\LevelTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Interfaces\ModelInterface;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\DetailTrait;

use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Broheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Inventories;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Vendors;

class Brodetails extends BaseModel implements ModelInterface{

	use LevelTrait, DetailTrait, SoftDeletes, \Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializeDetailTrait;

	protected $table = "brodetails";
    public $migration = "2022_00_00_09_brodetails.php";
	  protected $seed = [
        'dbf_brodetails'
      ];

        protected $attributeTypes = [ 
        "_config"=>"brodetails",
      ];

    public $foreignKeys = [
        ["TRANSNO","TRANSNO","broheads"], //TRANSNO references TRANSNO on allheads
        ["PROD_NO","ISBN","inventories"], //PROD_NO references ISBN on inventories
    ];

	public function head()
    {
        return $this->belongsTo(Broheads::class,'TRANSNO','TRANSNO');
    }
	
	public function vendor()
    {
        return $this->belongsTo(Vendors::class,'KEY','KEY');
    }
	
	public function book()
    {
        return $this->belongsTo(Inventories::class,'PROD_NO','ISBN');
    }
}
