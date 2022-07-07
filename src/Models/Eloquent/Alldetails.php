<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent;

use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\DetailTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Interfaces\ModelInterface;
use Illuminate\Database\Eloquent\SoftDeletes;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\LevelTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Allheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Inventories;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Vendors;

class Alldetails extends BaseModel implements ModelInterface{

	use DetailTrait, LevelTrait, SoftDeletes, \Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializeDetailTrait;

    public $timestamps = false;
	protected $table = "alldetails";
    public $migration = "2022_00_00_13_alldetails.php";
  protected $seed = [
    'dbf_alldetails'
  ];
        protected $attributeTypes = [ 
            "_config"=>"alldetail"/*,
            "_json" => '{"FUCKTRAN":{"name":"FUCKTRAN","type":"Integer","length":13},"JOBBERHOLD":{"name":"JOBBERHOLD","type":"Char","length":3},"ORDACTION":{"name":"ORDACTION","type":"Char","length":20},"ORDREASON":{"name":"ORDREASON","type":"Char","length":20},"TRANSNO":{"name":"TRANSNO","type":"String","length":10},"TESTTRAN":{"name":"TESTTRAN","type":"Integer","length":9},"ORDERNUM":{"name":"ORDERNUM","type":"Char","length":16},"KEY":{"name":"KEY","type":"Char","length":13},"DATE":{"name":"DATE","type":"Char","length":8},"PROD_NO":{"name":"PROD_NO","type":"Char","length":13},"REQUESTED":{"name":"REQUESTED","type":"Integer","length":5},"SHIPPED":{"name":"SHIPPED","type":"Integer","length":5},"ARTICLE":{"name":"ARTICLE","type":"Char","length":3},"AUTHOR":{"name":"AUTHOR","type":"Char","length":34},"TITLE":{"name":"TITLE","type":"Char","length":45},"LISTPRICE":{"name":"LISTPRICE","type":"Integer","length":8},"SALEPRICE":{"name":"SALEPRICE","type":"Integer","length":8},"CAT":{"name":"CAT","type":"Char","length":26},"INVNATURE":{"name":"INVNATURE","type":"Char","length":5},"SERIES":{"name":"SERIES","type":"Char","length":28},"SOPLAN":{"name":"SOPLAN","type":"Char","length":30},"DISC":{"name":"DISC","type":"Integer","length":4},"PUBLISHER":{"name":"PUBLISHER","type":"Char","length":20},"FORMAT":{"name":"FORMAT","type":"Char","length":15},"SUBTITLE":{"name":"SUBTITLE","type":"Char","length":50},"CATALOG":{"name":"CATALOG","type":"Char","length":4},"STATUS":{"name":"STATUS","type":"Char","length":20},"UNITCOST":{"name":"UNITCOST","type":"Integer","length":6},"TITLEKEY":{"name":"TITLEKEY","type":"Char","length":20},"AUTHORKEY":{"name":"AUTHORKEY","type":"Char","length":20},"COMPUTER":{"name":"COMPUTER","type":"Char","length":30},"TIMESTAMP":{"name":"TIMESTAMP","type":"Char","length":8},"DATESTAMP":{"name":"DATESTAMP","type":"Char","length":8},"LASTTOUCH":{"name":"LASTTOUCH","type":"Char","length":35},"LASTTIME":{"name":"LASTTIME","type":"Char","length":8},"LASTDATE":{"name":"LASTDATE","type":"Char","length":8},"PAGES":{"name":"PAGES","type":"Integer","length":4},"OUNCES":{"name":"OUNCES","type":"Integer","length":7},"PUBDATE":{"name":"PUBDATE","type":"Int","length":8},"REMOTEADDR":{"name":"REMOTEADDR","type":"Char","length":15},"USERPASS":{"name":"USERPASS","type":"Char","length":15},"ORDEREDBY":{"name":"ORDEREDBY","type":"Char","length":40},"EWHERE":{"name":"EWHERE","type":"Char","length":7},"SCARTONNO":{"name":"SCARTONNO","type":"Integer","length":9},"TRANSNUM":{"name":"TRANSNUM","type":"Integer","length":9},"F856NUM":{"name":"F856NUM","type":"Integer","length":9},"INDEX":{"name":"INDEX","type":"Int","length":50}}'*/
          ];

          protected $indexes = ["KEY"];

 protected $ignoreColumns = [
    "JOBBERHOLD","ORDACTION","ORDREASON","TESTTRAN","ORDERNUM","ARTICLE","INVNATURE","SERIES","PUBLISHER","FORMAT","SUBTITLE","CATALOG","STATUS","UNITCOST","TITLEKEY","AUTHORKEY","COMPUTER","TIMESTAMP","DATESTAMP","LASTTOUCH","LASTTIME","LASTDATE","PAGES","OUNCES","PUBDATE","REMOTEADDR","USERPASS","ORDEREDBY","EWHERE","SCARTONNO","TRANSNUM","F856NUM","LASTDATE","PUBLISHER","SOPLAN","CAT","TESTTRAN","deleted_at"
 ];

    public $foreignKeys = [
        ["TRANSNO","TRANSNO","allheads"], //TRANSNO references TRANSNO on allheads
        ["PROD_NO","ISBN","inventories"], //PROD_NO references ISBN on inventories
    ];

	public function head()
    {
        return $this->belongsTo(Allheads::class,'TRANSNO','TRANSNO');
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
