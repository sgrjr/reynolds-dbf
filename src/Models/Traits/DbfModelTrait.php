<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Traits;

use Sreynoldsjr\ReynoldsDbf\ReynoldsDbf;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Sreynoldsjr\ReynoldsDbf\Events\FailedWritingToDbf;
use Sreynoldsjr\ReynoldsDbf\Events\NewDbfEntryCreated;
use Sreynoldsjr\ReynoldsDbf\Events\ExistingDbfEntryUpdated;

trait DbfModelTrait {

    public function dbf(){
        return ReynoldsDbf::model($this->getTable());
    }

    public function dbfDelete()
    {
        $this->DELETED = true;
        $result = $this->dbf()->save();
        if($result){$this->save();}
        return true;
    }

 public function dbfSave()
    {
        $table = $this->dbf();
        $table->open();
        $atttributes_from_dbf = $table->save($this->toArray());
        $table->close();
        $this->update($atttributes_from_dbf);
        return $this;
    }

public static function dbfCreate(){
    dd("Ask Trait line 56 not implemented.");
}

public static function dbfUpdateOrCreate($graphql_root, $attributes, $request=false, $x=false, $user=false) {
    $isNewEntry = true;

     if(isset($request) && $request !== false && $user === false){
          $user = $request->user();
         } else if($user === false){
          $user = request()->user();
        }
         
     if(isset($attributes["input"])){$attributes = $attributes["input"];}

     //Setting the Model
     if(static::class === "App\Models\Webdetail" && !isset($attributes["id"]) ){
             
         if(!isset($attributes["REMOTEADDR"])){// if vendor has no carts then create one.
              $newcart = \App\Models\Webhead::newCart($user->vendor);
              $newcart->save();
              $newcart->dbf()->save();

            $attributes['REMOTEADDR'] = $newcart->REMOTEADDR;
            $attributes['KEY'] = $newcart->KEY;
        }

        //If this is a title being added to the order than we need to check if the PROD_NO already exists or not
        // on the order with REMOTEADDR made by user with KEY.
        $model = $user->vendor->webdetailsOrders()->where('REMOTEADDR',$attributes["REMOTEADDR"])->where("PROD_NO",$attributes["PROD_NO"])->first();
        if(!$model || $model === null){
            //If the title wasn't already on the order then just create a new order item.
            $model = (new static($attributes))->fillAttributes($user);
            $model->save();
            NewDbfEntryCreated::dispatch($model, $user->id);
        }else{
            $isNewEntry = false;
            //Was already on order so update model attributes with the passed new attributes.
            foreach($attributes AS $k=>$v){
                if($k === "REQUESTED"){
                    $model->$k = $model->$k+$v;
                }else{
                    $model->$k = $v;
                }
            }
            ExistingDbfEntryUpdated::dispatch($model, $user->id);
        }

     }else{
        
        //Check if $attributes do not have an id and therefore create a new $model;
         if(!isset($attributes["id"])){
            $model = (new static($attributes))->fillAttributes($user);
            $model->save();
         }else{
            //$attributes['id'] was set and therefore the $model already exists so query it.
            $model = static::where('id', $attributes['id'])->where('KEY', $user->KEY)->first(); 
            $isNewEntry = false; 
         }

         if($model === null){
            $isNewEntry = true;
            unset($attributes["id"]);
            $model = (new static($attributes))->fillAttributes($user);
         }else{
            foreach($attributes AS $k=>$v){
                $model->$k = $v;
            }
         }
    }       
            
     if($model){
        $model->dbfSave();

        //Check which event to fire
        if($isNewEntry){
            NewDbfEntryCreated::dispatch($model, $user->id);
        }else{
            ExistingDbfEntryUpdated::dispatch($model, $user->id);
        }    

     }else{
        \App\Helpers\Misc::dbfLog('Could not write to dbf and or database. There is probably now a blank entry in DBF because of this function fail. ' . static::class . " attributes: " . json_encode($attributes));
        FailedWritingToDbf::dispatch($model, $user->id);
     }
     return $user;
}
public function fillAttributes($user = false){return $this;}

public function setIfNotSet($key, $val, $force = false, $func_arg = false){

    if($force || !isset($this->$key) || $this->$key === null || $this->$key === false){
        if(method_exists($this,$val)){
            $this->$key = $this->$val($func_arg);
        }else{
            $this->$key = $val;
        }
    }
    return $this;
}
}