<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent;

use App\Models\Webhead;
use App\Models\Inventory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\DetailTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\LevelTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Interfaces\ModelInterface;

use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Webheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Inventories;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Vendors;

class Webdetails extends BaseModel implements ModelInterface {

  use DetailTrait, LevelTrait, SoftDeletes, \Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializeDetailTrait;
   
   	public $timestamps = false;
	protected $table = "webdetails";
    public $migration = "2022_00_00_05_webdetails.php";
    protected $seed = [
        'dbf_webdetails'
    ];

    protected $indexes = ["KEY"];
    protected $appends = [];
    protected $attributeTypes = [ 
        "_config"=>"webdetails",
    ];

    protected $fillable = ["REQUESTED", "REMOTEADDR", "PROD_NO", "INDEX", "KEY","deleted_at",
                    "DATE",
                    "ORDEREDBY",
                    "ARTICLE",
                    "TITLE",
                    "AUTHOR",
                    "LASTTOUCH",
                    "SHIPPED",
                    "LISTPRICE",
                    "SALEPRICE",
                    "DISC",
                    "STATUS",
                    "SUBTITLE",
                    "PUBLISHER",
                    "FORMAT",
                    "SERIES",
                    "SOPLAN",
                    "CAT",
                    "AUTHORKEY",
                    "TITLEKEY",
                    "COMPUTER",
                    "TIMESTAMP",
                    "DATESTAMP",
                    "LASTTIME",
                    "UNITCOST",
                    "PAGES",
                    "PUBDATE",
                    "INVNATURE",
                    "USERPASS",
                    "LASTDATE",
                    ];

    public $foreignKeys = [
        ["REMOTEADDR","REMOTEADDR","webheads"], //REMOTEADDR references REMOTEADDR on webheads
        ["PROD_NO","ISBN","inventories"], //PROD_NO references ISBN on inventories
    ];

// This function doe snot make sense now that I am using soft deletes
    public function scopeDeleted($query)
    {
        return $query->withTrashed();
    }

    public function getBookConnection(array $record = []){

	    if(empty($record)){
	      $isbn = $this->getAttributes()["PROD_NO"];
	    }else{
	      $isbn = $record["PROD_NO"];
	    }
	    
	    return Inventory::ask()->where("ISBN","===", $isbn)->first();
	  }

	  	public function webdetailSchema($table){$table->foreign('REMOTEADDR')->references('REMOTEADDR')->on('webheads'); return $table;	}

	  	public function cart(){
	  		return $this->belongsTo(Webheads::class, "REMOTEADDR", "REMOTEADDR");
	  	}

	  public function getCoverArtAttribute(){
	    return url("/img/small/" . $this->attributes['PROD_NO'] . ".jpg");
	  }

	 public function user(){
	    return $this->belongsTo('\App\Models\User','vendor_key','KEY');
	  }


public function fillAttributes($user = false){

   //fininsh figuring creating and updating carttitles and eventually deleting them
    if(!$user){$user = request()->user();}

    $now = \Carbon\Carbon::now();

    $this
      ->setIfNotSet('REQUESTED', 1)
      ->setIfNotSet('KEY',$user->KEY)
      ->setIfNotSet('REMOTEADDR','getRemoteAddr', false, $user)
      ->setIfNotSet('SHIPPED', 0)
      ->setIfNotSet('PROD_NO',null);

    // Set Attributes Related to Book
    $book = Inventory::where('ISBN', $this->PROD_NO)->first();
    if($book === null){
        return false;
    }
    $bookAtts = ["ARTICLE","TITLE","AUTHOR","LISTPRICE","STATUS","AUTHORKEY","TITLEKEY","FORMAT","SERIES","PUBLISHER","CAT","PAGES","PUBDATE","INVNATURE","SOPLAN"];
    foreach($bookAtts AS $att){
        $this->setIfNotSet($att, $book->$att);
    }

    //Set Attributes Viewer/Vendor Related
    $viewerTitleData = $book->getUserData($user);
    //viewerTitleData is returning an empty object WHY WHY WHY WhY ????

      $this
      ->setIfNotSet('LISTPRICE',round(floatval($book->LISTPRICE),2), true)
      ->setIfNotSet('SALEPRICE',$viewerTitleData->price, true)
      ->setIfNotSet('DISC',$viewerTitleData->discount, true)
      ->setIfNotSet('DATE',\Carbon\Carbon::now()->format("Ymd"))
      ->setIfNotSet('DATESTAMP',\Carbon\Carbon::now()->format("Ymd"))
      ->setIfNotSet('LASTDATE',\Carbon\Carbon::now()->format("Ymd"), true)
      ->setIfNotSet('TIMESTAMP',\Carbon\Carbon::now()->format("H"))
      ->setIfNotSet('LASTTIME',\Carbon\Carbon::now()->format("H"), true)
      ->setIfNotSet('ORDEREDBY',$user->SNAME)
      ->setIfNotSet('LASTTOUCH',$user->SNAME, true)
      ->setIfNotSet('COMPUTER',$user->SNAME)
      ->setIfNotSet('USERPASS',null);//disabled as returns TOO LONG error Why whould UPASS be needed here anyhow? $user->UPASS      

      return $this;
  }

public function getRemoteAddr($user){
    $cart = Webheads::dbfUpdateOrCreate(false, [], false, false, $user);
    return $cart->REMOTEADDR;
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
