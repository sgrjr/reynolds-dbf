<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent;

use Illuminate\Database\Eloquent\SoftDeletes;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\LevelTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Interfaces\ModelInterface;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\DetailTrait;

use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Ancientheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Inventories;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Vendors;

class Ancientdetails extends BaseModel implements ModelInterface {
	
	use LevelTrait, DetailTrait, SoftDeletes, \Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializeDetailTrait;

	protected $table = "ancientdetails";
    public $migration = "2022_00_00_15_ancientdetails.php";
    protected $seed = [
        'dbf_ancientdetails'
    ];
    protected $dbfPrimaryKey = "TRANSNO";

      protected $attributeTypes = [ 
        "_config"=>"ancientdetails",
      ];

      protected $indexes = ["KEY"];

      protected $ignoreColumns = [ 
        "PAGES","OUNCES","TESTTRAN","USERPASS","ORDERNUM","CAT","SUBTITLE","ARTICLE","LASTTOUCH","LASTTIME","LASTDATE","TITLEKEY","AUTHORKEY","UNITCOST","ORDEREDBY","PUBDATE","FORMAT","COMPUTER","INVNATURE","FORMAT","PUBLISHER","CATALOG","STATUS","SOPLAN","TIMESTAMP","DATESTAMP","SERIES","REMOTEADDR"
      ];

      protected $fillable = ["KEY","TRANSNO","DATE","REQUESTED","SHIPPED","PROD_NO","AUTHOR","TITLE","LISTPRICE","DISC","SALEPRICE","SERIES","INDEX"];

    public $foreignKeys = [
        ["TRANSNO","TRANSNO","ancientheads"], //TRANSNO references TRANSNO on ancientheads
        ["PROD_NO","ISBN","inventories"], //PROD_NO references ISBN on inventories
    ];


	public function head()
    {
        return $this->belongsTo(Ancientheads::class,'TRANSNO','TRANSNO');
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
