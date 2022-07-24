<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent;

use Cache, Carbon\Carbon, stdclass;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Passfiles;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Allheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Ancientheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Backheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Broheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Webheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Standing_orders;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Passwords;
use Symfony\Component\Console\Output\ConsoleOutput;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Interfaces\ModelInterface;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializeVendorsTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\EloquentVendorsTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\AddressesCacheTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\CacheTrait;

use Sreynoldsjr\ReynoldsDbf\Models\Vendors as DbfVendors;
use Sreynoldsjr\ReynoldsDbf\Models\Brodetails as DbfBrodetails;
use Sreynoldsjr\ReynoldsDbf\Models\Backdetails as DbfBackdetails;
use Sreynoldsjr\ReynoldsDbf\Models\Alldetails as DbfAlldetails;
use Sreynoldsjr\ReynoldsDbf\Models\Ancientdetails as DbfAncientdetails;
use Sreynoldsjr\ReynoldsDbf\Models\Webdetails as DbfWebdetails;
use Sreynoldsjr\ReynoldsDbf\Models\Broheads as DbfBroheads;
use Sreynoldsjr\ReynoldsDbf\Models\Backheads as DbfBackheads;
use Sreynoldsjr\ReynoldsDbf\Models\Allheads as DbfAllheads;
use Sreynoldsjr\ReynoldsDbf\Models\Ancientheads as DbfAncientheads;
use Sreynoldsjr\ReynoldsDbf\Models\Webheads as DbfWebheads;
use Sreynoldsjr\ReynoldsDbf\Models\Standing_orders as DbfStanding_orders;
use Sreynoldsjr\ReynoldsDbf\Models\Passwords as DbfPasswords;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class Vendors extends BaseModel implements ModelInterface {

  use SoftDeletes;
  use InitializeVendorsTrait;
  use EloquentVendorsTrait;
  use AddressesCacheTrait, CacheTrait;

    private $VENDOR_CACHE_MINUTES = 15;
    public $timestamps = false;
    protected $connection = "mysql";
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

      public function getCacheIsbns(){

        $cache_key = $this->KEY . "_isbns";
        Cache::forget($cache_key);
        $key = $this->KEY;
        return Cache::rememberForever($cache_key, function() use ($key) {
          return $this->getCacheHistory($key);
        });
      }

      public function getCacheHistory(String $key = null){
        if(!$key) $key = $this->KEY;
        if(!$key) return static::emptyHistory();

          $dir = storage_path('/app/vendors');

          if(!is_dir($dir)) return static::emptyHistory($key);// static::cacheEverything(); //this doesn't seem to make sense to cache now on the request as the request will just timeout to complete this

          $file_name = $dir . '/'.$key.'_vendor.cache';

          if(file_exists($file_name)){
            return json_decode(file_get_contents($file_name));
          }else{
            return static::emptyHistory($key);
          }
            
      }
      public static function emptyHistory(String $key = null){
            $empty = new stdclass;
            $empty->updated_at = '';
            $empty->KEY = $key;
            $empty->purchased = collect([]);
            $empty->standing_orders = collect([]);
            $empty->addresses = collect([]);
            
            return $empty;
      }

     public static function cachePurchased($class, Collection $keys = null){
          ini_set('memory_limit','512M');
          if(!$keys) $keys = static::startCacheKeys();

          $output = new ConsoleOutput;
          $steps = (new $class)->count();
          $message = 'Purchased Books being cached from: ' . $class;
          $progressBar = static::progressBar($output, $steps, $message);

          $replace = false;
          $class::loop(function($record)use($keys, $replace, $progressBar){
               if($record->KEY != "" && $keys->get($record->KEY) != null){
                    $keys[$record->KEY]->purchased->push($record->PROD_NO);
                    static::storeVendor($keys[$record->KEY], 'purchased', $replace);
                    $keys[$record->KEY]->purchased = collect([]);
               }

               $progressBar->advance();
          }, -1, 999999);

          $progressBar->finish();

          return true;
     }

     public static function cacheWebdetails(Collection $keys = null){
          static::cachePurchased(DbfWebdetails::class, $keys);
     }

     public static function cacheBrodetails(Collection $keys = null){
           static::cachePurchased(DbfBrodetails::class, $keys);
     }

     public static function cacheBackdetails(Collection $keys = null){
          static::cachePurchased(DbfBackdetails::class, $keys);
     }

     public static function cacheAlldetails(Collection $keys = null){
           static::cachePurchased(DbfAlldetails::class, $keys);
     }

     public static function cacheAncientdetails(Collection $keys = null){
          static::cachePurchased(DbfAncientdetails::class, $keys);
     }

     private static function cachePlans(Collection $keys = null){
          ini_set('memory_limit','512M');
          if(!$keys) $keys = static::startCacheKeys();
          $m = new static;
          $m->w('Starting to cache standing orders ...');

           /* GET STANDING ORDERS */
          foreach(DbfStanding_orders::all(['INDEX','KEY','QUANTITY','SOSERIES']) AS $record){
               static::addIf($keys, $record, 'standing_orders');
          }

          $filtered = $keys->filter(function ($value, $key) {
               return $value->standing_orders->count() > 0;
          });

          static::storeCache($filtered, 'standing_orders', true);

          $m->w('Standing orders cached.');
     }

     private static function getBillingFields(){
          return [
               'KEY','BILL_1','BILL_2','BILL_3','BILL_4','BILL_5',
               'COMPANY','ATTENTION','STREET','ROOM','DEPT','CITY','STATE','POSTCODE','COUNTRY','VOICEPHONE','FAXPHONE'
          ];
     }

     private static function cacheAddressesVendors(Collection $keys = null){
          if(!$keys) $keys = static::startCacheKeys();
          static::addressesFromVendorFile($keys);          
     }

     private static function cacheAddressesWebheads(Collection $keys = null){
          if(!$keys) $keys = static::startCacheKeys();
          static::addressesFromHeadsFile($keys, DbfWebheads::class);  

          $filtered = $keys->filter(function ($value, $key) {
               return $value->addresses->count() > 0;
          });

          static::storeCache($filtered, 'addresses', true);        
     }

      private static function cacheAddressesAllheads(Collection $keys = null){
          if(!$keys) $keys = static::startCacheKeys();
          static::addressesFromHeadsFile($keys, DbfAllheads::class);  

          $filtered = $keys->filter(function ($value, $key) {
               return $value->addresses->count() > 0;
          });

          static::storeCache($filtered, 'addresses', true);        
     }

     private static function cacheAddresses(Collection $keys = null){

          if(!$keys) $keys = static::startCacheKeys();

          // These Return $keys
          static::addressesFromHeadsFile($keys, DbfWebheads::class);
          static::addressesFromHeadsFile($keys, DbfBroheads::class);
          static::addressesFromHeadsFile($keys, DbfBackheads::class);
          static::addressesFromHeadsFile($keys, DbfAllheads::class);
          static::addressesFromHeadsFile($keys, DbfAncientheads::class);

          $filtered = $keys->filter(function ($value, $key) {
               return $value->addresses->count() > 0;
          });

          static::storeCache($filtered, 'addresses', true);
          
     }

     public static function cacheOrders(){
        ini_set('memory_limit','512M');
          $keys = static::startCacheKeys();

          /* GET ISBNS PURCHASED */
         static::cacheWebdetails($keys);
         static::cacheBrodetails($keys);
         static::cacheBackdetails($keys);
         static::cacheAlldetails($keys);
         static::cacheAncientdetails($keys);
     }

     public static function cacheEverything(){
          
          $m = new static;
          $m->w('Started at: ' . now()->toDateTimeString());

          $keys = static::startCacheKeys();
          
          $output = new ConsoleOutput;
          $steps = 9;
          $message = 'Everything is being cached.';
          $progressBar = static::progressBar($output, $steps, $message);

          static::cacheClear();
          $progressBar->advance();

          //static::cachePlans($keys);
          //$progressBar->advance();

          //static::addressesFromVendorFile($keys);
          //$progressBar->advance();

          /* GET ISBNS PURCHASED */
          static::cacheWebdetails($keys);
          $progressBar->advance();

          static::cacheBrodetails($keys);
          $progressBar->advance();

          static::cacheBackdetails($keys);
          $progressBar->advance();

          static::cacheAlldetails($keys);
          $progressBar->advance();

          static::cacheAncientdetails($keys);
          $progressBar->advance();

          static::cacheAddresses();
          
          $progressBar->finish();

          $m->w('Finished at: ' . now()->toDateTimeString());
     }

     private static function addIf($keys, $record, $prop_name){
          if(
               $record['KEY'] != "" && 
               $record['KEY'] !== null && 
               isset($record['KEY']) && 
               $keys->get($record['KEY'])
          ) {
               //if(is_array($keys[$record['KEY']]->$prop_name)) $keys[$record['KEY']]->$prop_name = collect($keys[$record['KEY']]->$prop_name);
               $keys[$record['KEY']]->$prop_name->push($record);
          }
          return $keys;
     }

     private static function startCacheKeys(Array $k = []){

          if(empty($k)) $k = DbfVendors::unique('KEY');
          $keys = collect([]);
          $now = Carbon::now()->toDateTimeString();

          foreach($k AS $i=>$ke){
               $keys->put($ke , (Object) [
                    'created_at' => $now, // maybe it makes sense here, I'm not sure
                    'updated_at'=> $now,
                    'KEY'=> $ke, 
                    "purchased" => collect([]), 
                    "standing_orders" => collect([]), 
                    "addresses" => collect([])
               ]);
          }

          return $keys;
     }

     private static function storeCache($vendors, $prop, $replace = true){
          foreach($vendors AS $key=>$vendor){
              static::storeVendor($vendor, $prop, $replace);
          }
     }

     private static function file_name($key){
          return $file_name = "/vendors/" . $key . "_vendor.cache";
     }
     private static function storeVendor($vendor, $prop, $replace){
          $file_name = static::file_name($vendor->KEY );

          if(Storage::has($file_name)){
               $f = json_decode(Storage::get($file_name));
               if($replace){
                    $f->$prop = $vendor->$prop;
               }else{
                    $f->$prop = $vendor->$prop->merge($f->$prop);
               }

               $f->updated_at = $vendor->updated_at;
               $vendor = $f;
          }
          Storage::put($file_name, json_encode($vendor));
     }

     public static function cacheClear(){
          (new static)->w('Clearing cache...');
          Storage::deleteDirectory('vendors');
     }

     public static function cacheClearPurchased(){
          static::clearAProperty(['purchased', collect([])]);
     }

     public static function clearAProperty($props){
          ini_set('memory_limit','512M');
          $keys = static::startCacheKeys();

          foreach($keys AS $key){
               $file_name = static::file_name($key->KEY );
               
               if(Storage::has($file_name)){
                    $data = json_decode(Storage::get($file_name));
                    foreach($props AS $p){
                         $name = $p['name'];
                         $data->$name = $p['value'];
                    }
                    Storage::put($file_name, json_encode($data));
               }
          }

     }
}
