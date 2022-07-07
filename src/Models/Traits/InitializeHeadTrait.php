<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Traits;

use Cache, Config;
use Sreynoldsjr\ReynoldsDbf\Models\Vendors;

use Sreynoldsjr\ReynoldsDbf\Models\Allheads;
use Sreynoldsjr\ReynoldsDbf\Models\Ancientheads;
use Sreynoldsjr\ReynoldsDbf\Models\Backheads;
use Sreynoldsjr\ReynoldsDbf\Models\Broheads;
use Sreynoldsjr\ReynoldsDbf\Models\Webheads;

trait InitializeHeadTrait {

public function initialize(){

     /*if($this->attributes['KEY'] == null){
        $message = 'Model MUST be given a KEY. given: ' . json_encode($this->getAttributes());
        throw new \ErrorException($message);
     }*/

    if(is_string($this->attributes['KEY']) && strlen($this->attributes['KEY']) != $this->database()->getColumnByName('KEY')->length && strlen($this->attributes['KEY']) > 0){
        $message = 'KEY is not the correct length : '
        . strlen($this->KEY)
        . ' given but ' 
        . $this->database()->getColumnByName('KEY')->length
        . ' expected.';

        throw new \ErrorException($message);
     }

     foreach($this->attributes AS $key=>$val){
        if(is_string($val)){
          $this->attributes[$key] = $this->transformToDataEntry($key, $val);
        }
      }

      $vendor = $this->vendor;
      if($vendor && $vendor->KEY != "") $this->initFromVendor($vendor);

      //\Session::put("use_cart",$REMOTEADDR);
      $now = \Carbon\Carbon::now();
      $user = request()->user;

      if(!is_null($user)) $this->initCartFromUser($user);

      $this
        ->setIfNotSet('KEY', $this->KEY)
        ->setIfNotSet('REMOTEADDR',$this->generateRemoteAddr($this->KEY))
        ->setIfNotSet('OSOURCE', "INTERNET ORDER")
        ->setIfNotSet('ISCOMPLETE', false)
        ->setIfNotSet('DATE', $now->format("Ymd"))
        ->setIfNotSet('ISCOMPLETE', false)
        ->setIfNotSet('OSOURCE','INTERNET ORDER')
        ->setIfNotSet('DATE', $now->format("Ymd"))
        ->setIfNotSet('DATESTAMP', $now->format("Ymd"))
        ->setIfNotSet('LASTDATE', $now->format("Ymd"))
        ->setIfNotSet('TIMESTAMP', $now->format("h:i:s"))
        ->setIfNotSet('LASTTIME', $now->format("h:i:s"))
        ->setIfNotSet('PSHIP', 7)
        ->setIfNotSet('PIPACK', 7)
        ->setIfNotSet('PEPACK', 7)
        ;

      return $this;

  }

  public function initFromVendor($vendor){
      // to get values for: 
      //BILL_1, BILL_2, BILL_3, BILL_4, BILL_5, COMPANY, ATTENTION, STREET, ROOM, DEPT, CITY, STATE, POSTCODE

      //1. Check for most recent entry for vendor in Brohead (NOTE: Skip over matches where OSOURCE != "DAILY_ORDERS")

      $recentOrder = $vendor->broOrders()->where('OSOURCE', "LIKE", "%DAILY ORDERS%")->orderBy('INDEX','DESC')->first();
      if($recentOrder === null){
        //2. If no entry in Brohead then Check for most recent entry in Allhead (SAME NOTE AS in #1)
        $recentOrder = $vendor->allOrders()->where('OSOURCE', "LIKE", "%DAILY ORDERS%")->orderBy('INDEX','DESC')->first();
      }

      $recentOrder = new Broheads($recentOrder);

    
      /* 
        IF an order was found in Brohead or Allhead init from it
        ELSE 3. Create Cart from vendor
      */
      !is_null($recentOrder)? $this->initCartFromHead($recentOrder):$this->initCartFromVendor($vendor); 

      return $this;
  }

  // function modifies $newCart by adding values from $vendor
  public function initCartFromVendor($vendor){

    /* MAKES SURE ARGS HAVE VENDOR RELATED INFO */

    $this
      ->setIfNotSet('BILL_1', trim($vendor->ARTICLE) . " " . trim($vendor->ORGNAME) )// trim(ARTICLE)+ " " trim(ORGNAME))
      ->setIfNotSet('BILL_2', $vendor->STREET); 
      
      if(trim($vendor->SECONDARY != "")){
        //a) If there is an entry in vendor->SECONDARY
        $this
          ->setIfNotSet('BILL_3', $vendor->SECONDARY)
          ->setIfNotSet('BILL_4', trim(trim($vendor->CARTICLE) . " " . trim($vendor->CITY) . ", " . $vendor->STATE . " " . $vendor->ZIP5))
          ->setIfNotSet('ATTENTION', $vendor->SECONDARY);
      }else{
        $this
          ->setIfNotSet('BILL_3', trim(trim($vendor->CARTICLE) . " " . trim($vendor->CITY) . ", " . $vendor->STATE . " " . $vendor->ZIP5))
          ->setIfNotSet('BILL_4', "")
          ->setIfNotSet('ATTENTION', $vendor->SECONDARY);
      }

      $this
      ->setIfNotSet('VOICEPHONE', $vendor->VOICEPHONE)
      ->setIfNotSet('FAXPHONE', $vendor->FAXPHONE)
      ->setIfNotSet('EMAIL', $vendor->EMAIL)
      ->setIfNotSet('BILL_5', "")
      ->setIfNotSet('ROOM', "")
      ->setIfNotSet('DEPT', "")
      ->setIfNotSet('COMPANY', trim(trim($vendor->ARTICLE) . " " . trim($vendor->ORGNAME)))
      ->setIfNotSet('STREET',  $vendor->STREET)
      ->setIfNotSet('CITY', trim(trim($vendor->CARTICLE) . " " . trim($vendor->CITY)))
      ->setIfNotSet('STATE', $vendor->STATE)
      ->setIfNotSet('POSTCODE', $vendor->ZIP5);

    return $this;
  }
  // function modifies $newCart by adding values from existing head record
    public function initCartFromHead($head){

      /* MAKES SURE ARGS HAVE MINIMAL PROPERTIES BY COPYING FROM ANOTHER RECORD. */

      $this
      ->setIfNotSet('VOICEPHONE', trim($head->VOICEPHONE))
      ->setIfNotSet('FAXPHONE', trim($head->FAXPHONE))
      ->setIfNotSet('EMAIL', trim($head->EMAIL))
      ->setIfNotSet('BILL_1', trim($head->BILL_1))
      ->setIfNotSet('BILL_2', trim($head->BILL_2))
      ->setIfNotSet('BILL_3', trim($head->BILL_3))
      ->setIfNotSet('BILL_4', trim($head->BILL_4))
      ->setIfNotSet('BILL_5', trim($head->BILL_5))
      ->setIfNotSet('COMPANY', trim($head->COMPANY))
      ->setIfNotSet('ATTENTION', trim($head->ATTENTION))
      ->setIfNotSet('STREET', trim($head->STREET))
      ->setIfNotSet('ROOM', trim($head->ROOM))
      ->setIfNotSet('DEPT', trim($head->DEPT))
      ->setIfNotSet('CITY', trim($head->CITY))
      ->setIfNotSet('STATE', trim($head->STATE))
      ->setIfNotSet('POSTCODE', trim($head->POSTCODE));

    return $this;
  }

  public static function generateRemoteAddr($key = false){

    if($key){
      $uid=  time();//uniqid();
      $zip = substr($key,0,5);
      return substr("1".$zip . $uid, 0,15);
    }else{
      $list = true;

      $all = Broheads::unique('REMOTEADDR')->merge(
        Backheads::unique('REMOTEADDR')
      )->merge(
        Webheads::unique('REMOTEADDR')
      )->merge(
        Allheads::unique('REMOTEADDR')
      )->merge(
        Ancientheads::unique('REMOTEADDR')
      );

      $rm = trim($all->sort()->last()) + 1;

      return intval($rm);
    }

  }

  public static function getRemoteAddrs(){
    return static::query()->list('REMOTEADDR');
  }

   public function initCartFromUser($user){

    /* MAKES SURE ARGS HAVE USER RELATED INFO */

      $this
      ->setIfNotSet('ORDEREDBY',$user->UNAME)
      ->setIfNotSet('EMAIL',$user->EMAIL)
      ->setIfNotSet('USERPASS',$user->user_pass_unsafe)
      ->setIfNotSet('LASTTOUCH',$user->KEY)
      ->setIfNotSet('BILL_1',$user->vendor->ORGNAME)
      ->setIfNotSet('BILL_2',"c/o " . $user->vendor->FIRST . " " . $user->vendor->LAST)
      ->setIfNotSet('BILL_3',$user->vendor->STREET)
      ->setIfNotSet('BILL_4',$user->vendor->CITY . ", " . $user->vendor->STATE . " " . $user->vendor->ZIP5Y)
      ->setIfNotSet('COMPANY',$user->vendor->ORGNAME)
      ->setIfNotSet('STREET',$user->vendor->STREET)
      ->setIfNotSet('CITY',$user->vendor->CITY)
      ->setIfNotSet('STATE',$user->vendor->STATE)
      ->setIfNotSet('POSTCODE',$user->vendor->ZIP5)
      ->setIfNotSet('VOICEPHONE',$user->vendor->VOICEPHONE);

      return $this;
  }
		
}