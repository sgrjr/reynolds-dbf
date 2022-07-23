<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits;

use Config, stdclass;
use Sreynoldsjr\ReynoldsDbf\Models\Vendors as DbfVendors;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Vendors;
use Sreynoldsjr\ReynoldsDbf\Models\Passwords as DbfPasswords;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Passwords;
use Sreynoldsjr\ReynoldsDbf\Models\Webheads as DbfWebheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Webheads;
use Sreynoldsjr\ReynoldsDbf\Models\Allheads as DbfAllheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Allheads;
use Sreynoldsjr\ReynoldsDbf\Models\Ancientheads as DbfAncientheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Ancientheads;
use Sreynoldsjr\ReynoldsDbf\Models\Broheads as DbfBroheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Broheads;
use Sreynoldsjr\ReynoldsDbf\Models\Backheads as DbfBackheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Backheads;
use Symfony\Component\Console\Output\ConsoleOutput;
use Illuminate\Support\Collection;

function convert($size)
{
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}

trait AddressesCacheTrait {

     private static function addressesFromVendorFile(Collection $keys = null){

          $attributes = static::getBillingFields();

          //$vendors = DbfVendors::query()->where('KEY','!=',null)->perPage(99999999)->get();
          $vendors = Vendors::where('KEY','!=',null)->get();
          //$ctr = 1;
          $replace = false;
          $list_of_keys = $keys->keys();
          $webhead = new Webheads;

          $output = new ConsoleOutput;
          $steps = count($vendors);
          $message = 'Addresses from Vendor File...';
          $progressBar = static::progressBar($output, $steps, $message);

          foreach($vendors AS $record){
                    if(!is_array($record)) $record = $record->toArray();
                    //var_dump($ctr);
                    //var_dump(convert(memory_get_usage(true)));

                    //$user = DbfPasswords::query()->where('KEY', '==',$record->KEY)->first();
                    //$user = Passwords::where('KEY', $record['KEY'])->first();

                    //$newCart = DbfWebheads::newCart($record, $user);
                    $newCart = $webhead->makeCart($record);
                    //var_dump(convert(memory_get_usage(true)));
                    /*$newCart = new class{
                         function __get($prop){
                              return null;
                         }
                    };
                    */
                    $vendor = $keys[$record['KEY']];
                    $address = [];

                    foreach($attributes AS $att){
                         $address[$att] = $newCart->$att;
                    }
                    if(is_array($vendor->addresses)) $vendor->addresses = collect($vendor->addresses);
                    $vendor->addresses->push($address);

                    static::storeVendor($vendor, 'addresses', $replace);
                    $keys[$record['KEY']]->addresses = collect([]);
                    $keys[$record['KEY']]->purchased = collect([]);
                    $keys[$record['KEY']]->standing_orders = collect([]);
                    $progressBar->advance();     
          }
          $progressBar->finish();
     }

      private static function addressesFromHeadsFile($keys, $head){

          $columns = static::getBillingFields();
          $steps = (new $head)->count();
          $replace = false;
          $perPage = 1000;
          $pages = (int) ceil($steps/$perPage);

          $output = new ConsoleOutput;
          $message = 'Addresses from '.$head.' File...';
          $progressBar = static::progressBar($output, $steps, $message);

          for($i=1; $i<=$pages; $i++){

               $progressBar->setMessage('Page '.$i.' of ' . $pages);

               $results = $head::query()->where('OSOURCE', "==", "DAILY ORDERS")->perPage($perPage)->page($i)->get($columns);
               //var_dump($steps);
               //var_dump($pages);
               //var_dump($i);
///*
               $keys = static::startCacheKeys();

               foreach($results AS $record){
                    static::addIf($keys, $record, 'addresses');
                    $progressBar->advance();
               }

               $filtered = $keys->filter(function($record){
                    return $record->addresses->count() > 0;
               });

              static::storeCache($filtered, 'addresses', $replace);
//*/
          }

          $progressBar->finish();
          return $keys;
     }
		
}