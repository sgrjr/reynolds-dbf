<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent;

//Events (shouuld move all this kind of logic elsewhere sometime)
//it is certainly overloading these models

use Illuminate\Database\Eloquent\SoftDeletes;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\HeadTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\DbfValidationTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Interfaces\ModelInterface;

use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Passwords;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Vendors;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Webdetails;

class Webheads extends BaseModel implements ModelInterface {
   
   use HeadTrait, DbfValidationTrait, SoftDeletes, \Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializeHeadTrait;

  public $fillable = ["INDEX","KEY","ATTENTION", "DATE","BILL_1","BILL_2","BILL_3","BILL_4","COMPANY","STREET","CITY","STATE","POSTCODE","VOICEPHONE","OSOURCE","ISCOMPLETE", "ROOM","DEPT","COUNTRY","FAXPHONE","EMAIL","SENDEMCONF","PO_NUMBER","CINOTE","CXNOTE","TRANSNO","DATESTAMP","TIMESTAMP","LASTDATE","LASTTIME","LASTTOUCH","REMOTEADDR","PSHIP","PIPACK","PEPACK","deleted_at"];
    public $migration = "2022_00_00_04_webheads.php";
    public $timestamps = false;
    protected $appends = [];
    protected $table = "webheads";
    protected $indexes = ["REMOTEADDR", "KEY"];
    protected $dbfPrimaryKey = 'REMOTEADDR';
    protected $seed = [
        'dbf_webheads'
    ];

  protected $requiredAttributes = [
    "KEY",
    "DATE",
    "DATESTAMP",
    "TIMESTAMP",
    "LASTDATE",
    "LASTTIME",
    "LASTTOUCH",
    "REMOTEADDR"
  ];

  protected $attributeTypes = [ 
    "_config"=>"webheads",
  ];

    public function scopeIscomplete($query)
    {
        return $query->where('PSHIP', 5)->where('PIPACK', 5)->where('PEPACK', 5);
    }

    public function scopeNotcomplete($query)
    {
        return $query->where('PSHIP',"!=",5)->where('PIPACK',"!=",5)->where('PEPACK',"!=", 5);
    }

    public function scopeDeleted($query)
    {
        return $query->withTrash();
    }


  // $record passed to getDetailsConnection must be an associative array
  // resulting from XBaseRecord->getRawData()


  public function items(){
    return $this->hasMany(Webdetails::class,'REMOTEADDR','REMOTEADDR');
  }

  public function passwords(){
    return $this->belongsTo(Passwords::class,'KEY','KEY');
  }

  public function vendor(){
    return $this->belongsTo(Vendors::class,'KEY','KEY');
  }

  public function getVendorConnection(array $record = []){

    if(empty($record)){
      $key = $this->getAttributes()["KEY"];
    }else{
      $key = $record["KEY"];
    }
    
    if(strpos($key,"04046") !== false){
      $vendor = new \App\Models\Vendor;
      $vendor->KEY = $key;
      $vendor->ORGNAME = "FAKE COMPANY";
      return $vendor;
    }else{
      return Vendors::dbf()->where("KEY","===", $key)->first();
    }
  }

    public function updateShipping()
    {

      $records = $this->getDetailsConnection();
      $count = $records->paginator->count;
      $vendor = $this->getVendorConnection();
      if($vendor !== null){
         $SOLIST = $vendor->activeStandingOrders->records;   
      }else{
        $vendor = new \stdclass;
         $SOLIST = [];
      }
     
      $trade = 0;
      $cp = 0;

      foreach($records->records AS $att){

        if($att->INVNATURE !== "TRADE" && $att->referenceStandingOrderList($vendor->KEY, $SOLIST)->isInList){
          $cp += $att->REQUESTED;
        }else{
          $trade++;
        }

      }

      if($cp >= 5){
        $this->SHIPPING = 0.00;
      }
      
      $this->dbfSave();
      
      return $this;
    }

  public function submitOrder($props = false){

    if($props){
      foreach($props AS $k => $v){
        $this->$k = $v;
      }
    }
    
    $this->ISCOMPLETE = 1;
    $this->PSHIP = 5;
    $this->PIPACK = 5;
    $this->PEPACK = 5;
    $this->dbfSave();
    
    return $this;
  }

    public function getMyCart($_, $args){

        $user = request()->user();

        if(isset($args['id'])){
            $cart = static::where('id', $args['id'])->where('KEY', $user->KEY)->first();
        }else{
            $cart = static::where('REMOTEADDR', $args['REMOTEADDR'])->where('KEY', $user->KEY)->first();
        }
        return $cart;
    }

    public static function carts($_, $args){
        if(isset($args['dbf']) && $args['dbf'] === true){
            return (new static)->dbf();
        }else{
            return new static;
        }
    }

     public static function addTitleToCartMutation($user, $args){
    
        if($user === null){
            $user = request()->user();
        }

        $user->addTitleToCart($args['input']);
 
        return $user;
    }


     public static function removeTitleFromCartMutation($user, $args){
    
        if($user === null){
            $user = request()->user();
        }

        $user->removeTitleFromCart($args["REMOTEADDR"], $args["PROD_NO"]);
 
        return $user;
    }

     public static function createCartMutation($user, $args){
    
        if($user === null){
            $user = request()->user();
        }

        $cart = $user->newCart();
        $cart->save();
 
        return $user;
    }

     public static function updateCartMutation($user, $args){
    
        if($user === null){
            $user = request()->user();
        }

        if(isset($args['id'])){
          $cart = $user->carts()->where('id',$args['id'])->first();
          unset($args['id']);
        }else if(isset($args['INDEX'])){
          $cart = $user->carts()->where('INDEX',$args['INDEX'])->first();
          unset($args['INDEX']);
        }else if(isset($args['REMOTEADDR'])){
          $cart = $user->carts()->where('REMOTEADDR',$args['REMOTEADDR'])->first();
          unset($args['REMOTEADDR']);
        }else{
          $cart = $user->newCart();
          $cart->save();
        }

        $cart->update($args['input']);
 
        return $user;
    }




     public static function destroyCartMutation($user, $args, $request){

        if($user === null){
            $user = request()->user();
        }
      
        $cart = static::where('id', $args['id'])->where('KEY', $user->KEY)->first();

        if($cart !== null){

          if($user->vendor->cartsCount <= 1){
            $run = true;
          }
          $cart->dbfDelete();
        }

        if($run){
            $newcart = $user->newCart();
            if($newcart){
              $newcart->save();
            }
        }

      return $user;
  }

    public static function updateCartTitleMutation($user, $args){
    
        if($user === null){
            $user = request()->user();
        }

        $cart = $user->updateCartTitle($args['input']);
 
        return $user;
    }

protected static function boot()
    {
    /*
    EVENTS:
    creating and created: sent before and after records have been created.
    updating and updated: sent before and after records are updated.
    saving and saved: sent before and after records are saved (i.e created or updated).
    deleting and deleted: sent before and after records are deleted or soft-deleted.
    restoring and restored: sent before and after soft-deleted records are restored.
    retrieved: sent after records have been retrieved.
    */
        parent::boot();

        static::creating(function ($model) {
          // move curren tlogic in MUTATOR to here using "CREATE" directive in schema later
            //$model = static::prepareNewCartTitle($model);
            
        });

        static::saving(function ($model) {
          //return $model->dbf()->isAvailable();
        });

        static::saved(function ($model) {
            if($model->INDEX === null){
                $result = $model->dbf()->create($model->getAttributes());
                $model->fill($result->getAttributes())->save();
            }else{
               return $result = $model->dbf()->save();
            }

        });

        static::updating(function ($model) {
            //$model = static::prepareUpdateCartTitle($model);
        });

        static::deleted(function ($model) {
            $dbf = $model->fromDbf();
            if($dbf != null){
               $dbf->delete();
            }
        });

        static::restored(function ($model) {
            $dbf = $model->fromDbf();
            if($dbf != null){
               $dbf->restore();
            }
        });
    }

}
