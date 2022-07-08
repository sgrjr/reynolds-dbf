<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent;

use Illuminate\Database\Eloquent\SoftDeletes;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\HeadTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Interfaces\ModelInterface;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Models\Ancientdetails;

class Ancientheads extends BaseModel implements ModelInterface {

use HeadTrait, SoftDeletes, \Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializeHeadTrait;

protected $table = "ancientheads";
protected $dbfPrimaryKey = 'TRANSNO';
protected $appends = [];
protected $seed = ['dbf_ancientheads'];
protected $indexes = ["TRANSNO", "KEY"];
public $migration = "2022_00_00_14_ancientheads.php";
 protected $attributeTypes = [ 
  "_config"=>"ancientheads", 
 ];

 protected $fillable = ["KICKBACK", "INVQUERY", "INVLMNT", "PROMONAME", "TESTTRAN", "NEWPRODUCT", "TPRODUCT", "USERPASS","ORDEREDBY", "PINVOICE", "PEPACK", "PIPACK", "PSHIP", "COMPUTER", "TIMESTAMP", "DATESTAMP", "LASTTOUCH", "LASTDATE", "LASTTIME", "REVDATE", "UPSDATE", "BILLWEIGHT", "PACKAGES", "COMMCODE", "OLDCODE", "OSOURCE2","OSOURCE3", "OSOURCE4", "CHECKDESC", "PAYTYPE", "ONSLIP", "SPECIALD", "REMOTEADDR", "TAXEXEMPT", "CANBILL", "DATEIN", "TIMEIN", "TIMEOUT", "SHIPPER", "SORTORDER", "DATEOUT", "HOTBOX", "TRANSNUM", "F997SENT", "F997NUM", "F855SENT", "F855NUM", "F856SENT", "F856NUM", "F810SENT", "F810NUM", "SHIPLABEL", "OSETNUM", "AREVEIW", "ORSTATUS", "ORSENT", "ORDATE","NEWITEMS","ITEMS","PRODUCT","TITEMS","TERMS","SHIPMETHOD"
 ];

 public function items(){
  return $this->hasMany(Ancientdetails::class, 'TRANSNO', 'TRANSNO');
 }
}
