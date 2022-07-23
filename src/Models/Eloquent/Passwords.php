<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent;

use Carbon, Session, Config, Request, Auth, Event, Schema, stdClass;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Auth\Passwords\CanResetPassword;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\ModelTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\ManageTableTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\PresentableTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\GetsPermissionTrait;
use Symfony\Component\Console\Output\ConsoleOutput;
use Illuminate\Database\Eloquent\SoftDeletes;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\DbfModelTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Webheads;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Vendors;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Interfaces\ModelInterface;
use Sreynoldsjr\ReynoldsDbf\Helpers\Application;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\InitializePasswordsTrait;

class Passwords extends BaseModel implements ModelInterface {

  use ManageTableTrait,
    ModelTrait,
    PresentableTrait,
    GetsPermissionTrait,
    DbfModelTrait,
    SoftDeletes,
    InitializePasswordsTrait;

    protected $fillable = ['KEY', 'UPASS', 'MPASS', 'UNAME', 'SNAME', 'EMAIL', 'PIC', 'COMPANY', 'SEX', 'FIRST', 'MIDNAME', 'LAST', 'ARTICLE', 'TITLE', 'ORGNAME', 'STREET', 'SECONDARY', 'CITY', 'CARTICLE', 'STATE', 'COUNTRY', 'POSTCODE', 'NATURE', 'VOICEPHONE', 'EXTENSION', 'FAXPHONE', 'COMMCODE', 'CANBILL', 'TAXEXEMPT', 'SENDEMCONF', "LOGINS","DATEUPDATE","DATESTAMP","MDEPT","MFNAME","TSIGNOFF","TIMESTAMP","TIMEUPDATE","PASSCHANGE","PRINTQUE","SEARCHBY","MULTIBUY","SORTBY","FULLVIEW","SKIPBOUGHT","OUTOFPRINT","OPROCESS","OBEST","OADDTL","OVIEW","ORHIST","OINVO","EXTZN","INSOS","INREG","LINVO","NOEMAILS","ADVERTISE","PROMOTION","PASSDATE","EMCHANGE",'INDEX', 'deleted_at', 'created_at', 'updated_at'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
        protected $hidden = [
        ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
	
	protected $appends = ["public_id"];
    //php artisan migrate --path=/database/migrations/full_migration_file_name_migration.php
	public $migration = "2022_00_00_01_passwords.php";
    
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'passwords';
	protected $presenter = 'Sreynoldsjr\ReynoldsDbf\Presenters\PasswordPresenter';

    protected $dbfPrimaryKey = 'INDEX';

      protected $seed = [
        'config_passwords',
        'dbf_passwords'
      ];

      protected $attributeTypes = [
        "_config"=>"passwords",
       'timestamps'=> true
      ];

    protected $casts = [
        'DATESTAMP' => 'date',
    ];

   public function getIndexesAttribute(){
    return ["KEY"];
   }

	public function getNameAttribute(){
       return $this->exists? $this->FIRST . " " . $this->LAST : null;
    }

    public function getApplicationAttribute(){
      return Application::props($this);
    }

    /**
     * Passwords must always be encrypted.
     *
     * @param $password
     */

  /*
    Mutators END
  */

  public static function failLogin(){
            $auth = new stdClass;
            $auth->error = new stdClass;
            $auth->error->code = 200;
            $auth->error->message = "Session destroyed successfully";
            $auth->token = null;
            $auth->user = new User;
            return $auth;
  }
 
    public function vendor()
    {
        return $this->belongsTo(Vendors::class, 'KEY','KEY');
    }

    public function carts()
    {
        return $this->hasMany(Webheads::class, 'KEY','KEY');
    }

  public static function updateProfile($firstname,$middlename,$lastname,$suffix,$gender,$profile_image,$location)
  {

  	$user = \Auth::user();

  	$user->username = strtolower($firstname).'-'.strtolower($middlename).'-'.strtolower($lastname).strtolower($suffix);
  	$user->firstname = $firstname;
  	$user->middlename = $middlename;
  	$user->lastname = $lastname;
  	$user->suffix = $suffix;
  	$user->gender = $gender;

  	if($profile_image !== null){
  		$user->profile_image = $profile_image;
  	}

  	$user->location = $location;

  	return $user;
  }

    public function getPhotoAttribute()
    {

        return '/img/profile-photo/'. $this->nameProfileImage($this);
    }

    public function passwordReset()
    {
    	$fromDate = \Carbon::now()->subDays(3);
    	$tillDate = \Carbon::now();

    	return $this->hasMany('App\Helpers\PasswordReset')
    		->whereBetween('created_at', [$fromDate, $tillDate])->first();
    }	


	public static function getGuest()
	{
	   $guest = new User;	   
       return $guest;
	}

  public function passwordsSchema($table){
		$table->foreign('KEY')->references('KEY')->on('vendors');
        $table->timestamps();

		return $table;
	}

public function getMemo(){
        $config = Config::get("cp");
        $tablename = $this->getTable();
        return $config["tables"][$tablename][2];
}

    public function getCount(){

        $x = new stdclass;
        $x->mysql = $this->count();
        $x->dbf = false;
        return $x;
    }

        public function getTableExistsAttribute(){
            return Schema::hasTable($this->getTable());
        }

    public function getAlternateUname(){
      return 'name';
    }

     public static function createCredentialsFromPasswordsTable($credentials){
        
       $email = isset($credentials["EMAIL"])? $credentials["EMAIL"]:$credentials["email"];
       $password = isset($credentials["UPASS"])? $credentials["UPASS"]:$credentials["password"];

      $user = \App\Models\User::dbf()
              ->where("EMAIL","===", $email)
              ->where("UPASS","===", $password)
              ->first();
             
      if($user === null){

        $conf = \Config::get('cp');

        foreach($conf['users'] AS $u){
          if($u['EMAIL'] === $email && $u['UPASS'] === $password){
            $user = static::create($u);
            break;
          }else{
            $user = false;
          }
        }
        return $user;
      }else{
        $user->password = $user->UPASS;
        $user->save();
      }

      return $user? true:false;

    }

    public function getAuthPassword() {
       return $this->UPASS;
    }

    public function getIsCustomerAttribute(){

      $hasCpEmail = strpos($this->EMAIL, "centerpointlargeprint");
      $isSuper = strpos($this->EMAIL, "deliverance.me");
  
      if($hasCpEmail !== false || $isSuper  !== false) {
        return false; 
       } else {
          return true;
        }
    }

        public function updateProfilePhoto($_, $args){
        
        $user = request()->user();
        $file = $args['profilePicture'];
        

        //File Name
        //$file->getClientOriginalName();

        //File Extension
        //$file->getClientOriginalExtension();
     
        //Display File Real Path
       //$file->getRealPath();
     
        //Display File Size
        //$file->getSize();
     
        //Display File Mime Type
        //$file->getMimeType();
     
        //Move Uploaded File
        $destinationPath = storage_path() . '/uploads';
        $filename = $this->nameProfileImage($user) . "." . $file->getClientOriginalExtension();
        $file->move($destinationPath,$filename);
        return $user;
    }

    private function nameProfileImage($user){
      return base64_encode($user->id . $user->EMAIL);
    }

    public static function findByHash($root, $args, $request){

      $id = $args['id'];

      if($request->user()->can("LIST_ALL_USERS") ){
        
        $id = base64_decode($id);
        return static::where('id',$id)->first();
      }
      return null;
    }

    public function getPublicIdAttribute(){
      return base64_encode($this->id);
    }

    // Determins if the passed $model Belongs to this user or not
    // returns Boolean
    public function isOwner($model){
        return $model->hasOwner($this);
    }

    public function deleteThis($model){
         if($model && $this->can("DELETE_RECORD", ["model"=>$model])) {
             $model->delete();
             return $model;
         }
         return false;
    }

    public function restoreThis($model){
         if($model && $this->can("DELETE_RECORD", ["model"=>$model])) {
             $model->restore();
             return $model;
         }
         return false;
    }

    public function saveThis($model){
         if($model && $this->can("UPDATE_RECORD", ["model"=>$model])){
             $model->save();
             return $model;
         } 
         return false;
    }

    public function newCart(){
        $vendor = [
            'KEY'=>$this->vendor->KEY,
            'ARTICLE'=>$this->vendor->ARTICLE,
            'ORGNAME'=>$this->vendor->ORGNAME,
            'STREET'=>$this->vendor->STREET,
            'SECONDARY'=>$this->vendor->SECONDARY,
            'CARTICLE'=>$this->vendor->CARTICLE,
            'CITY'=>$this->vendor->CITY,
            'STATE'=>$this->vendor->STATE,
            'ZIP5'=>$this->vendor->ZIP5,
            'VOICEPHONE'=>$this->vendor->VOICEPHONE,
            'FAXPHONE'=>$this->vendor->FAXPHONE,
            'EMAIL'=>$this->vendor->EMAIL,
            'COUNTRY'=>$this->vendor->COUNTRY
        ];

        $user = [
            'UNAME'=> $this->UNAME,
            'EMAIL'=> $this->EMAIL,
            'UPASS'=> $this->UPASS,
            'KEY'=> $this->KEY,
        ];

       $cart = Webheads::newCart($vendor, $user);
       return $cart;
    }

    public function addItemToCart($cart, $args){

        $cart->items()->make([
            "REQUESTED" => $args["REQUESTED"],
            "PROD_NO" => $args["PROD_NO"],
            "REMOTEADDR" => $cart->REMOTEADDR
        ]);

        return $cart;
    }

    public function addTitleToCart($args){

        //Check if this is a new cart
         if(!isset($args["REMOTEADDR"]) || $args["REMOTEADDR"] === "NEW_UNSAVED_CART" || $args["REMOTEADDR"] === "" || $args["REMOTEADDR"] === null){
            $cart = $this->newCart();
            $cart->save();
            return $this->addItemToCart($cart, $args)->save();

         }else{//load existing cart
            $cart = Webheads::where("REMOTEADDR",$args["REMOTEADDR"])->first();

            if($cart === null){
                $cart = $this->newCart();
                $cart->save();
                return $this->addItemToCart($cart, $args)->save();
            }
    
            if($this->can("ADD_TITLE_TO_CART", ["model"=>$cart]) ){
                $updated = false;

                foreach($cart->items AS $item){
                    if($item->PROD_NO === $args["PROD_NO"]){
                        $item->REQUESTED = $item->REQUESTED + $args["REQUESTED"];

                        //Save only if allowed
                        $this->saveThis($item);

                        $detail = $item;
                        $updated = true;
                    }
                }
                //if no entries were updated then create a new entry
                 if(!$updated){
                     //Save only if allowed
                    $this->saveThis($cart->items()->make([
                        "REQUESTED" => $args["REQUESTED"],
                        "PROD_NO" => $args["PROD_NO"],
                        "REMOTEADDR" => $args["REMOTEADDR"]
                    ])->fillAttributes($this));  
                }

                return $cart;
            }else{
                return false;
            }
         }

        
    }

     public function removeTitleFromCart($remoteaddr, $isbn){

        $cart = $this->carts()->where("REMOTEADDR", $remoteaddr)->first();

        if($cart){
            foreach($cart->items AS $item){
              if($item->PROD_NO === $isbn){
                $this->deleteThis($item);
              }
            }
        }

        return $this;
      }

    public function updateCartTitle($attributes){

        $cart = $this->carts()->where('REMOTEADDR',$attributes['REMOTEADDR'])->first();

        if($cart != null){
            $entries = $cart->items()->where("PROD_NO", $attributes['PROD_NO'])->get();
        }else{
            $entries = [];
        }

        unset($attributes['REMOTEADDR']);
        unset($attributes['PROD_NO']);
        unset($attributes['id']);

        foreach($entries AS $title){
            $title->update($attributes);
        }
        return $this;
      }



}
