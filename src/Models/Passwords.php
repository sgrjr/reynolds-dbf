<?php namespace Sreynoldsjr\ReynoldsDbf\Models;

use Sreynoldsjr\ReynoldsDbf\Models\Model;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializePasswordsTrait;

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
}