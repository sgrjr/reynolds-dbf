<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent;

use Illuminate\Database\Eloquent\SoftDeletes;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Interfaces\ModelInterface;

use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Passwords;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Vendors;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Alldetails;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\HeadTrait;

class Allheads extends BaseModel implements ModelInterface{

	use HeadTrait, SoftDeletes, \Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializeHeadTrait;
    	
	protected $table = "allheads";
	protected $appends = ['freeShipping'];
  public $migration = "2022_00_00_12_allheads.php";
	protected $seed = [
		'dbf_allheads'
	];
	protected $dbfPrimaryKey = 'TRANSNO';
    protected $attributeTypes = [ 
        "_config"=>"allheads",
      ];

      protected $indexes = ["TRANSNO", "KEY"];

 public $fillable = [
"KICKBACK","INVQUERY","INVLMNT","PROMONAME","MASTERPASS","MASTERDATE","TESTTRAN","NEWPRODUCT","TPRODUCT","PRODUCT","SALESTAX","NEWITEMS","ITEMS","TITEMS","USERPASS","OTHERDESC","SHIPMETHOD","ORDEREDBY","PINVOICE","PEPACK","PIPACK","PSHIP","COMPUTER","TIMESTAMP","DATESTAMP","LASTTOUCH","LASTDATE","LASTTIME","REVDATE","OSOURCE2","OSOURCE3","OSOURCE4","CHECKDESC","PAYTYPE","ONSLIP","SPECIALD","REMOTEADDR","TAXEXEMPT","CANBILL","DATEIN","TIMEIN","TIMEOUT","SHIPPER","SORTORDER","DATEOUT","HOTBOX","TRANSNUM","F997SENT","F997NUM","F855SENT","F855NUM","F856SENT","F856NUM","F810SENT","F810NUM","SHIPLABEL","OSETNUM","AREVEIW","ORSTATUS","ORSENT","ORDATE","BILLWEIGHT","PACKAGES","OLDCODE","DELETED"
 ];
    protected $with = [];
    protected $withCount = ['items'];
  public function items(){
    return $this->hasMany(Alldetails::class,'TRANSNO','TRANSNO');
  }

  public function passwords(){
    return $this->hasMany(Passwords::class,'KEY','KEY');
  }

  public function vendor(){
    return $this->belongsTo(Vendors::class,'KEY','KEY');
  }
}
