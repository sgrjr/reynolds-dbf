<?php namespace Sreynoldsjr\ReynoldsDbf\Models;

use Sreynoldsjr\ReynoldsDbf\Models\Model;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializePasswordsTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Vendors;
use Sreynoldsjr\ReynoldsDbf\Models\Brodetails;
use Sreynoldsjr\ReynoldsDbf\Models\Backdetails;
use Sreynoldsjr\ReynoldsDbf\Models\Alldetails;
use Sreynoldsjr\ReynoldsDbf\Models\Ancientdetails;
use Sreynoldsjr\ReynoldsDbf\Models\Webdetails;
use Sreynoldsjr\ReynoldsDbf\Models\Standing_orders;

use Cache, Carbon\Carbon, Storage;

class Passwords extends Model {

     use InitializePasswordsTrait;
     
     public $table = 'passwords';

     public function getVendorAttribute(){
          return Vendors::query()->where('KEY','==', $this->KEY)->first();
     }

     public function getPreviouslyPurchasedAttribute(){
          return explode("\n", file_get_contents(storage_path() . "/vendors/" . $this->KEY . "_purchased.cache"));
     }

     public function getSummaryAttribute(){
          $file = json_encode(file_get_contents(storage_path() . "/vendors/" . $this->KEY . "_vendor.cache"));
     }

     public function getAddressesAttribute(){
          return $this->getSummaryAttribute()['addresses'];
     }

     public function getStandingOrdersAttribute(){
          return $this->getSummaryAttribute()['standing_orders'];
     }

     public function getPurchasedAttribute(){
          $purchased = collect($this->getSummaryAttribute()['purchased'])->flatten();
          return $purchased;
     }

     public static function cacheIt($class, $columns, $keys, $name, $sub){

          $first = true;

          return $class::loop(function($record)use($keys, $name, $columns, $sub, $first){

               if($keys->contains($record->KEY)){
                    $file_name = "/vendors/" . $record->KEY . "_vendor.cache";

                    $vendor = json_decode(Storage::get($file_name));

                    // reset cache to empty
                    if($first) $vendor->$name->sub = [];

                    if(count($columns) === 1){
                         $columnName = $columns[0];
                         $record = $record->$columnName;
                    }else{
                         $record = [];

                         foreach($columns AS $column){
                              $record[$column] = $record->$column;
                         }
                    }

                    $vendor->$name->$sub[] = $record;

                    Storage::put($file_name, json_encode($vendor));

                    $first = false;

               }
          }, -1, $limit=99999999);
     }

     public static function cacheWebdetails($keys = false){
          if(!$keys) $keys = Vendors::unique('KEY');
          static::cacheIt(Webdetails::class, ['PROD_NO'], $keys, 'purchased','web');
     }

     public static function cacheBrodetails($keys = false){
          if(!$keys) $keys = Vendors::unique('KEY');
           static::cacheIt(Brodetails::class, ['PROD_NO'], $keys, 'purchased','bro');
     }

     public static function cacheBackdetails($keys = false){
          if(!$keys) $keys = Vendors::unique('KEY');
          static::cacheIt(Backdetails::class, ['PROD_NO'], $keys, 'purchased','back');
     }

     public static function cacheAlldetails($keys = false){
          if(!$keys) $keys = Vendors::unique('KEY');
           static::cacheIt(Alldetails::class, ['PROD_NO'], $keys, 'purchased','all');
     }

     public static function cacheAncientdetails($keys = false){
          if(!$keys) $keys = Vendors::unique('KEY');
          static::cacheIt(Ancientdetails::class, ['PROD_NO'], $keys, 'purchased','ancient');
     }

     public static function cacheWeb(){
          $keys = Vendors::unique('KEY');
          static::cacheWebdetails($keys);
     }

     public static function cacheBro(){
          $keys = Vendors::unique('KEY');
          static::cacheBrodetails($keys);
     }

     public static function cacheBack(){
          $keys = Vendors::unique('KEY');
          static::cacheBackdetails($keys);
     }

     public static function cacheAll(){
          $keys = Vendors::unique('KEY');
          static::cacheAlldetails($keys);
     }

     public static function cacheAncient(){
          $keys = Vendors::unique('KEY');
          static::cacheAncientdetails($keys);
     }

     public static function cacheFirst(){
          $keys = Vendors::unique('KEY');
          static::cacheAddressesAndStandingOrders($keys);
     }

     public static function cacheClear(){
          static::clearVendorCache();
     }

     public static function cacheEverything(){
          $keys = Vendors::unique('KEY');

        static::clearVendorCache();

         static::cacheAddressesAndStandingOrders($keys);

          /* GET ISBNS PURCHASED */
         static::cacheWebdetails($keys);
         static::cacheBrodetails($keys);
         static::cacheBackdetails($keys);
         static::cacheAlldetails($keys);
         static::cacheAncientdetails($keys);
     }

     public static function buildCache($method = false){
          if(!$method) return static::cacheEverything();
          $method = 'cache' . ucfirst($method);
          return static:: $method();
     }
    
     private static function addIf($keys, $record, $prop_name){
          if(isset($record['KEY']) && $keys->get($record['KEY'])) $keys[$record['KEY']][$prop_name]->push($record);
          return $keys;
     }

     private static function cacheAddressesAndStandingOrders($keys = false){
          
          if($keys === false) $keys = Vendors::unique('KEY');
          $now = Carbon::now();

          foreach($keys AS $i=>$key){
               $keys[$key] = ['updated_at'=> $now,'key'=>$key, "purchased"=>[
                    "ancient" => [],
                    "all" => [],
                    "bro" => [],
                    "web" => []
               ], "standing_orders" => collect([]), "addresses"=>collect([])];
               unset($keys[$i]);
          }

          /* GET ADDRESSES USED */
          $columns = ['KEY','BILL_1','BILL_2','BILL_3','BILL_4','BILL_5','OSOURCE'];

          foreach(Webheads::query()->where('OSOURCE', "==", "DAILY ORDERS")->get($columns) AS $record){
               $keys = static::addIf($keys, $record, 'addresses');
          }

          foreach(Broheads::query()->where('OSOURCE', "==", "DAILY ORDERS")->get($columns) AS $record){
               $keys = static::addIf($keys, $record, 'addresses');
          }

          foreach(Allheads::query()->where('OSOURCE', "==", "DAILY ORDERS")->get($columns) AS $record){
               $keys = static::addIf($keys, $record, 'addresses');
          }

          /*
          $filtered = $keys->filter(function ($value, $key) {
              return count($value['addresses']) > 0;
          });

          foreach($filtered AS $key=>$vendor){
               file_put_contents(storage_path() . "/vendors/" . $vendor['key'] . "_addresses.cache", json_encode($vendor));
               $keys[$key]['addresses']->empty();
          }
          */

          /* GET STANDING ORDERS */
          foreach(Standing_orders::all(['INDEX','KEY','QUANTITY','SOSERIES']) AS $record){
               $keys = static::addIf($keys, $record, 'standing_orders');
          }

          foreach($keys AS $key=>$vendor){
               Storage::put("/vendors/" . $vendor['key'] . "_vendor.cache", json_encode($vendor));
          }
     }

     public static function clearVendorCache(){
          Storage::deleteDirectory('vendors');
     }
}