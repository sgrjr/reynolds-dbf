<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent;

use Cache;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Passfiles;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Allheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Ancientheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Backheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Broheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Webheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Standing_orders;

use Illuminate\Database\Eloquent\SoftDeletes;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Interfaces\ModelInterface;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializeVendorsTrait;

class Vendors extends BaseModel implements ModelInterface {

    use SoftDeletes, InitializeVendorsTrait;

    private $VENDOR_CACHE_MINUTES = 15;
    public $timestamps = false;
	protected $table = "vendors";
    protected $indexes = ["KEY"];
    public $migration = "2022_00_00_02_vendors.php";
	protected $dbfPrimaryKey = 'KEY';
    protected $casts = [
        'KEY' => 'string',
    ];
      protected $seed = [
    'dbf_vendors'
  ];

      protected $attributeTypes = [ 
        "_config"=>"vendors",
      ];

      protected $appends = ['summary','isbns'];
      public $fillable = ['ACCTNOTE', 'ACOLLNOTE', 'ARTICLE', 'BUDGET', 'CARTICLE', 'CITY', 'CITYKEY', 'COMMCODE', 'COMPUTER', 'COUNTRY', 'CUSTNOTE', 'DATESTAMP', 'deleted_at', 'EMAIL', 'EMCHANGE', 'ENOTE', 'ENTRYDATE', 'EXTENSION', 'FAXPHONE', 'FIRST', 'INDEX', 'KEY', 'LAST', 'LASTDATE', 'LASTTIME', 'LASTTOUCH', 'MIDNAME', 'NATURE', 'NEWCODE', 'NOEMAILS', 'OLDCODE', 'ORDATE', 'ORGNAME', 'ORGNAMEKEY', 'ORSTATUS', 'PROMOTIONS', 'RECALLD', 'REMDATE', 'REMOVED', 'SECONDARY', 'SEX', 'STATE', 'STREET', 'TIMESTAMP', 'TITLE', 'VOICEPHONE', 'WEBSERVER', 'WHAT', 'ZIP5'];

	public function standingOrders()
    {
        return $this->hasMany(Standing_orders::class,'KEY','KEY');
    }
  public function activeStandingOrders()
    {
        return $this->hasMany(Standing_orders::class,'KEY','KEY')->active();
    }
  public function inactiveStandingOrders()
    {
        return $this->hasMany(Standing_orders::class,'KEY','KEY')->inactive();
    }

	public function broOrders()
    {
        return $this->hasMany(Broheads::class,'KEY','KEY');
    }
  public function brodetailsOrders()
    {
        return $this->hasMany(Brodetail::class,'KEY','KEY');
    }

      public function backOrders()
    {
        return $this->hasMany(Backheads::class,'KEY','KEY');
    }

      public function backdetailsOrders()
    {
        return $this->hasMany(Backdetails::class,'KEY','KEY');
    }

    public function ancientOrders()
    {
        return $this->hasMany(Ancientheads::class,'KEY','KEY');
    }

    public function ancientdetailsOrders()
    {
        return $this->hasMany(Ancientdetails::class,'KEY','KEY');
    }


    public function allOrders()
    {
        return $this->hasMany(Allheads::class,'KEY','KEY');
    }

    public function alldetailsOrders()
    {
        return $this->hasMany(Alldetails::class,'KEY','KEY');
    }

    public function webOrders()
    {
        return $this->hasMany(Allheads::class,'KEY','KEY');
    }

    public function webdetailsOrders()
    {
        return $this->hasMany(Webdetails::class,'KEY','KEY');
    }

    private function addUnique($parent_array, $array_to_add){

        $new_items = [];
        foreach($array_to_add AS $atd){

            if(count($parent_array) < 1){
                $new_items[] = $atd;
                break;
            }

            foreach($parent_array AS $pa){
                foreach($atd AS $key => $val){
                    if($val !== $pa[$key]){
                        $new_items[] = $atd;
                        break 3;
                    }
                }

            }
        }

        return array_merge($parent_array, $new_items);
    }

    public function getEveryAddress($count = 10){
            
            $addresses = [];
            //webhead
            $web = $this->webOrders()->select('BILL_1','BILL_2','BILL_3','BILL_4')->take($count)->get()->toArray();

            $addresses = $this->addUnique($addresses, $web);

            if(count($addresses) < $count){
                $back = $this->backOrders()->select('BILL_1','BILL_2','BILL_3','BILL_4')->take($count-count($addresses))->get()->toArray();
                 $addresses = $this->addUnique($addresses, $back);

                if(count($addresses) < $count){
                    $bro = $this->broOrders()->select('BILL_1','BILL_2','BILL_3','BILL_4')->take($count-count($addresses))->get()->toArray();
                     $addresses = $this->addUnique($addresses, $bro);

                    if(count($addresses) < $count){
                        $all = $this->allOrders()->select('BILL_1','BILL_2','BILL_3','BILL_4')->take($count-count($addresses))->get()->toArray();
                         $addresses = $this->addUnique($addresses, $all);

                         if(count($addresses) < $count){
                            $ancient = $this->ancientOrders()->select('BILL_1','BILL_2','BILL_3','BILL_4')->take($count-count($addresses))->get()->toArray();
                             $addresses = $this->addUnique($addresses, $ancient);
                         }
                    }
                }
            }
            
            return $addresses;
    }

    public function getIsbnsAttribute()
    {

          $key = $this->KEY . "_isbns";
          //Cache::forget($key);

          return Cache::remember($key, 900, function () {

            $all = $this->alldetailsOrders()->pluck('PROD_NO')->toArray();
            $ancient = $this->ancientdetailsOrders()->pluck('PROD_NO')->toArray();
            $back = $this->backdetailsOrders()->pluck('PROD_NO')->toArray();
            $bro = $this->brodetailsOrders()->pluck('PROD_NO')->toArray();
            $web = $this->webdetailsOrders()->pluck('PROD_NO')->toArray();
            $list = collect(array_merge($all, $ancient,$back,$bro,$web));
            $newList = [];

            foreach($list AS $title){
              $newList[$title] = $title;
            }
            return $newList;
          });
    }

    public function calcWholeSaleDisount(){
	  //wholesale discount

	  $passfile = Passfiles::ask()->where('KEY',"===",$this->present()->KEY)->first();

	  if($passfile !== null){
	    return $passfile->discount;
	  }else{
	    return false;
	  }

	}

	 public function users(){
         return $this->hasMany(User::class,'KEY','KEY');
    }

    public function processing(){
        return $this->hasMany(Webhead::class,'KEY','KEY')->iscomplete();
    }

    public function carts()
    {
        return $this->hasMany(Webhead::class, 'KEY','KEY')->notcomplete();
    }

    public function getCartsCountAttribute(){

         return count($this->carts);
      }

      public function getProcessingCountAttribute(){

         return count($this->processing);
      }

         public function getSummaryAttribute(){

            return [
                "carts_count" => $this->cartsCount,
                "processing_count" => $this->processingCount 

            ];

          }


    public function getInvoice($_, $args){

        if(isset($args['TRANSNO'])){
            $key = 'TRANSNO';
        }else if(isset($args['REMOTEADDR'])){
            $key = 'REMOTEADDR';
        }else{
            return null;
        }

        $user = request()->user();
        $cart = Allheads::where($key, $args[$key])->where('KEY', $user->KEY)->first();

        if($cart === null){
          $cart = Ancientheads::where($key, $args[$key])->where('KEY', $user->KEY)->first();

          if($cart === null){
            $cart = Backheads::where($key, $args[$key])->where('KEY', $user->KEY)->first();

            if($cart === null){
              $cart = Broheads::where($key, $args[$key])->where('KEY', $user->KEY)->first();

              if($cart === null){
                $cart = Webheads::where($key, $args[$key])->where('KEY', $user->KEY)->first();
                  if($cart === null){
                    if($key === "TRANSNO"){
                        $cart = Webheads::where('REMOTEADDR', $args[$key])->where('KEY', $user->KEY)->first();
                    }else{
                        $cart = Webheads::where('TRANSNO', $args[$key])->where('KEY', $user->KEY)->first();
                    }
                    
                  }
              }
            }
          }
        }

        return $cart;
    }

    public function getAddressesAttribute(){
        return Cache::get($this->KEY.'_every_address', function () {
            return $this->getEveryAddress(6);
        });
    }

}
