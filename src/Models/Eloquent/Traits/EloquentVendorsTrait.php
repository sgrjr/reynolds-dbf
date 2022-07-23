<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits;

use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Standing_orders;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Broheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Brodetails;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Backheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Backdetails;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Ancientheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Ancientdetails;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Allheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Alldetails;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Webheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Webdetails;

use Cache;

trait EloquentVendorsTrait
{   

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
        return $this->hasMany(Brodetails::class,'KEY','KEY');
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

    public function getIsbnsAttribute()
    {
        return $this->getCache('isbns');
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
        return $this->hasMany(Webheads::class,'KEY','KEY')->iscomplete();
    }

    public function carts()
    {
        return $this->hasMany(Webheads::class, 'KEY','KEY')->notcomplete();
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
        return $this->getCacheHistory()->addresses;
    }

    public function getPurchasedTitlesAttribute(){
        return $this->getCacheHistory()->purchased;
    }
}