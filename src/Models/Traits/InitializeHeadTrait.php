<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Traits;

use Cache, Config;
use Sreynoldsjr\ReynoldsDbf\Models\Vendors;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Allheads;
use Sreynoldsjr\ReynoldsDbf\Models\Ancientheads;
use Sreynoldsjr\ReynoldsDbf\Models\Backheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Broheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Webheads;

use Sreynoldsjr\ReynoldsDbf\Models\Broheads as DbfBroheads;
use Sreynoldsjr\ReynoldsDbf\Models\Webheads as DbfWebheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Allheads as DbfAllheads;

trait InitializeHeadTrait {

public function initialize($vendor = false, $user = false){ // vendor should be an array of attributes=>values

    $this->validateInitialize();

     foreach($this->attributes AS $key=>$val){
        if(is_string($val)){
          $this->attributes[$key] = $this->transformToDataEntry($key, $val);
        }
      }

      if($vendor){
        if($vendor['KEY'] !== null) $this->initFromVendor($vendor);
      } 

      //\Session::put("use_cart",$REMOTEADDR);
      $now = \Carbon\Carbon::now();
      if(!$user) $user = request()->user;
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

  private function validateInitialize(){

     if(!isset($this->attributes['KEY']) || $this->attributes['KEY'] === ""){
        $message = 'Model MUST be given a KEY. given: ' . json_encode($this->getAttributes());
        throw new \ErrorException($message);
     }

     // Additional Check only if this is a DBF Model
     if(!str_contains(static::class, 'Eloquent')){
        if(is_string($this->attributes['KEY']) && strlen($this->attributes['KEY']) != $this->database()->getColumnByName('KEY')->length && strlen($this->attributes['KEY']) > 0){
          $message = 'KEY is not the correct length : '
          . strlen($this->KEY)
          . ' given but ' 
          . $this->database()->getColumnByName('KEY')->length
          . ' expected.';

          throw new \ErrorException($message);
       }
     }
  }

  public function getRecentOrder($vendor){
     if(str_contains(static::class,'Eloquent') ){
          $all_model = new Allheads;
          $bro_model = new Broheads;
        }else{
          $all_model = new DbfAllheads;
          $bro_model = new DbfBroheads;
        }

      $recentOrder = $bro_model->where('OSOURCE', "LIKE", "%DAILY ORDERS%")->where('KEY',"==",$vendor['KEY'])->orderBy('INDEX','DESC')->first();

      if($recentOrder === null){
        //2. If no entry in Brohead then Check for most recent entry in Allhead (SAME NOTE AS in #1)
        $recentOrder = $all_model->where('OSOURCE', "LIKE", "%DAILY ORDERS%")->orderBy('INDEX','DESC')->where('KEY',"==",$vendor['KEY'])->first();
      }

      return $recentOrder;
  }
  public function initFromVendor($vendor){
      // to get values for: 
      //BILL_1, BILL_2, BILL_3, BILL_4, BILL_5, COMPANY, ATTENTION, STREET, ROOM, DEPT, CITY, STATE, POSTCODE

      //1. Check for most recent entry for vendor in Brohead (NOTE: Skip over matches where OSOURCE != "DAILY_ORDERS")
       
       $recentOrder = $this->getRecentOrder($vendor);

      /* 
        IF an order was found in Brohead or Allhead init from it
        ELSE 3. Create Cart from vendor
      */
      if(!is_null($recentOrder) ){
        $this->initCartFromHead($recentOrder); 
      }else{
        $this->initCartFromVendor($vendor);
      }

      return $this;
  }

  // function modifies $newCart by adding values from $vendor
  public function initCartFromVendor(Array $vendor){

    /* MAKES SURE ARGS HAVE VENDOR RELATED INFO */
    if(is_array($vendor)) $vendor = (Object) $vendor;
    $this
      ->setIfNotSet('BILL_1', trim($vendor->ARTICLE ?? '') . " " . trim($vendor->ORGNAME ?? '') )// trim(ARTICLE)+ " " trim(ORGNAME))
      ->setIfNotSet('BILL_2', $vendor->STREET); 
      
      if(trim($vendor->SECONDARY != "")){
        //a) If there is an entry in vendor->SECONDARY
        $this
          ->setIfNotSet('BILL_3', $vendor->SECONDARY)
          ->setIfNotSet('BILL_4', trim(trim($vendor->CARTICLE ?? '') . " " . trim($vendor->CITY ?? '') . ", " . $vendor->STATE . " " . $vendor->ZIP5) ?? '')
          ->setIfNotSet('ATTENTION', $vendor->SECONDARY);
      }else{
        $this
          ->setIfNotSet('BILL_3', trim(trim($vendor->CARTICLE ?? '') . " " . trim($vendor->CITY ?? '') . ", " . $vendor->STATE . " " . $vendor->ZIP5) ?? '')
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
      ->setIfNotSet('COMPANY', trim(trim($vendor->ARTICLE ?? '') . " " . trim($vendor->ORGNAME ?? '')))
      ->setIfNotSet('STREET',  $vendor->STREET)
      ->setIfNotSet('CITY', trim(trim($vendor->CARTICLE ?? '') . " " . trim($vendor->CITY ?? '') ?? ''))
      ->setIfNotSet('STATE', $vendor->STATE)
      ->setIfNotSet('COUNTRY', $vendor->COUNTRY)
      ->setIfNotSet('POSTCODE', $vendor->ZIP5);

    return $this;
  }
  // function modifies $newCart by adding values from existing head record
    public function initCartFromHead($head){

      /* MAKES SURE ARGS HAVE MINIMAL PROPERTIES BY COPYING FROM ANOTHER RECORD. */

      $this
      ->setIfNotSet('VOICEPHONE', trim($head->VOICEPHONE ?? ''))
      ->setIfNotSet('FAXPHONE', trim($head->FAXPHONE ?? ''))
      ->setIfNotSet('EMAIL', trim($head->EMAIL ?? ''))
      ->setIfNotSet('BILL_1', trim($head->BILL_1 ?? ''))
      ->setIfNotSet('BILL_2', trim($head->BILL_2 ?? ''))
      ->setIfNotSet('BILL_3', trim($head->BILL_3 ?? ''))
      ->setIfNotSet('BILL_4', trim($head->BILL_4 ?? ''))
      ->setIfNotSet('BILL_5', trim($head->BILL_5 ?? ''))
      ->setIfNotSet('COMPANY', trim($head->COMPANY ?? ''))
      ->setIfNotSet('ATTENTION', trim($head->ATTENTION ?? ''))
      ->setIfNotSet('STREET', trim($head->STREET ?? ''))
      ->setIfNotSet('ROOM', trim($head->ROOM ?? ''))
      ->setIfNotSet('DEPT', trim($head->DEPT ?? ''))
      ->setIfNotSet('CITY', trim($head->CITY ?? ''))
      ->setIfNotSet('STATE', trim($head->STATE ?? ''))
      ->setIfNotSet('COUNTRY', trim($head->COUNTRY ?? ''))
      ->setIfNotSet('POSTCODE', trim($head->POSTCODE ?? ''));

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

      $rm = trim($all->sort()->last() ?? 0) + 1;

      return intval($rm);
    }

  }

  public static function getRemoteAddrs(){
    return static::query()->list('REMOTEADDR');
  }

   public function initCartFromUser($user){

     if(is_array($user)) $user = (Object) $user;

    /* MAKES SURE ARGS HAVE USER RELATED INFO */

      $this
      ->setIfNotSet('ORDEREDBY',$user->UNAME)
      ->setIfNotSet('EMAIL',$user->EMAIL)
      ->setIfNotSet('USERPASS',$user->UPASS)
      ->setIfNotSet('LASTTOUCH',$user->KEY);

      return $this;
  }

  public static function newCart(Array $vendor, $user = false){
     $cart = (new static(['KEY'=>$vendor['KEY']]));
     $cart->initCartFromVendor($vendor);
     return $cart;
  }

  public function makeCart($vendor){
    $this->attributes = ['KEY'=> $vendor['KEY']];
    return $this->initCartFromVendor($vendor);
  }

  public static function init(Array $vendor, Array $user = [], Array $head = []){

    $now = \Carbon\Carbon::now();

    $attributes = [
      'deleted_at'  => null,
      'KEY'         => $vendor['KEY'],
      'REMOTEADDR'  => static::generateRemoteAddr($vendor['KEY']),
      'OSOURCE'     => 'INTERNET ORDER',
      'PO_NUMBER'   => null,
      'ISCOMPLETE'  => null,
      'DATE'        => $now->format("Ymd"),
      'DATESTAMP'   => $now->format("Ymd"),
      'LASTDATE'    => $now->format("Ymd"),
      'TIMESTAMP'   => $now->format("h:i:s"),
      'LASTTIME'    => $now->format("h:i:s"),
      'PSHIP'       => 7,
      'PIPACK'      => 7,
      'PEPACK'      => 7,
      'BILL_1'      => trim(trim($vendor['ARTICLE'] ?? '') . " " . trim($vendor['ORGNAME'] ?? '')),
      'BILL_2'      => trim($vendor['SECONDARY']),
      'BILL_3'      => trim($vendor['STREET']),
      'BILL_4'      => trim(trim($vendor['CARTICLE'] ?? '') . " " . trim($vendor['CITY'] ?? '') . ", " . trim($vendor['STATE']) . " " . trim($vendor['ZIP5']) ?? ''),
      'BILL_5'      => null,
      'ROOM'        => null,
      'DEPT'        => null,
      'ATTENTION'   => trim($vendor['SECONDARY']),
      'VOICEPHONE'  => trim($vendor['VOICEPHONE']),
      'FAXPHONE'    => trim($vendor['FAXPHONE']),
      'EMAIL'       => trim($vendor['EMAIL']),
      'COMPANY'     => trim(trim($vendor['ARTICLE'] ?? '') . " " . trim($vendor['ORGNAME']) ?? ''),
      'STREET'      => trim($vendor['STREET']),
      'CITY'        => trim(trim($vendor['CARTICLE'] ?? '') . " " . trim($vendor['CITY'] ?? '') ?? ''),
      'STATE'       => trim($vendor['STATE']),
      'COUNTRY'     => trim($vendor['COUNTRY']),
      'POSTCODE'    => trim($vendor['ZIP5']),
    ];

    // Adjustments if Necessary
    if($attributes['ATTENTION'] === ""){
      //If there is no entry in vendor->SECONDARY
      $attributes['BILL_3'] = $attributes['BILL_4'];
      $attributes['BILL_4'] = "";
    }

    $get_from_head = ['BILL_1','BILL_2','BILL_3','BILL_4','BILL_5','SECONDARY','ROOM','DEPT','ATTENTION','VOICEPHONE','FAXPHONE','COMPANY','STREET','CITY','STATE','COUNTRY','POSTCODE'];

    // Overwrite with Head if Provided
    foreach($head AS $key=>$val){
      if(in_array($key,$get_from_head)) $attributes[$key] = $val;
    }

    if(count($user) > 0){
      $attributes['ORDEREDBY'] = $user['SNAME'];
      $attributes['EMAIL'] = $user['email'];
      $attributes['USERPASS'] = $user['UPASS'];
      $attributes['LASTTOUCH'] = $user['SNAME'];
    }

    // GET INDEX
    $attributes['INDEX'] = (new static)->dbf()->count();

    return $attributes;

  }
		
}