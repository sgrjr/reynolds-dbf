<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent;

use \Illuminate\Database\Eloquent\SoftDeletes;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Interfaces\ModelInterface;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializePassfilesTrait;

class Passfiles extends BaseModel implements ModelInterface{

    use SoftDeletes, InitializePassfilesTrait;

	protected $table = "passfiles";
	public $migration = "2022_00_00_10_passfiles.php";
	protected $appends = [];
	protected $seed = ['dbf_passfiles'];
	protected $dbfPrimaryKey = 'INDEX';
	public $timestamps = false;
  protected $attributeTypes = [ 
    "_config"=>"passfiles",
  ];

  //protected $ignoreColumns = ["DUNNDAYS"];
  public $fillable = [
  	"COMPANY","COUNTRY", "DATE","INDEX","deleted_at","DISCOUNT","DUNNDAYS","EMAIL","KEY","LISTPRICE","ORDER","ORGNAME","PASSWORD",
  	"SALEPRICE","STANDING","VISION","WEBSERVER","WHATCOLOR"
  	];
}
