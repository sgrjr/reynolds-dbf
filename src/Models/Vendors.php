<?php namespace Sreynoldsjr\ReynoldsDbf\Models;

use Sreynoldsjr\ReynoldsDbf\Models\Model;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializeVendorsTrait;

use Sreynoldsjr\ReynoldsDbf\Models\Broheads;

class Vendors extends Model {

     use InitializeVendorsTrait;

     public $table = 'vendors';
     
     public function allOrders(){
          return Allheads::query()->where('KEY','==',$this->KEY);
     }

     public function ancientOrders(){
          return Ancientheads::query()->where('KEY','==',$this->KEY);
     }

     public function broOrders(){
          return Broheads::query()->where('KEY','==',$this->KEY);
     }

     public function backOrders(){
          return Backheads::query()->where('KEY','==',$this->KEY);
     }

     public function webOrders(){
          return Webheads::query()->where('KEY','==',$this->KEY);
     }

     public function orders(){
          return array_merge(
               $this->allOrders->get(), 
               $this->ancientOrders->get(), 
               $this->broOrders->get(), 
               $this->backOrders->get(), 
               $this->webOrders->get()
          );
     }
}