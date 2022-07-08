<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits;
use Config, Schema;
use Illuminate\Database\Eloquent\Builder;

Trait ModelTrait
{
    public function getTableExistsAttribute(){
        return \Schema::hasTable($this->getTable());
    }

    public function getSeeds(){

        $s = [];
        $seeds = isset($this->seed) && $this->seed !== null? $this->seed:[];

        foreach($seeds AS $seed){
            $x = explode("_",$seed, 2);
            if($x[0] === "dbf"){
                $path = \Config::get('reynolds-dbf')['files'][$x[1]];
                
			}else if($x[0] === "config"){
                $path = null;
			}

            $s[] = [
                "type"=> $x[0],
                "id"=> $x[1],
                "path"=> $path
            ];
		}

        return $s;
    }

    public function getAttributeTypes(){
        if(isset($this->attributeTypes)){
            return $this->attributeTypes;  
		}else{
            return [];  
		}
	}

    public function isFromDbf(){
        $ans = false;

        foreach($this->getSeeds() AS $seed){
            if($seed["type"] === "dbf"){$ans = true;}
        }

        return $ans;
    }

    public function getIndexesAttribute(){
        return $this->indexes;
    }

  public function scopeNewest(Builder $query): Builder {
    return $query->orderBy("id","DESC");
  }

  // There is a conflict with the Eloquent model if I name this function "index()"
  public static function findByIndex($index, $withTrashed = false){
      if($withTrashed){
          return static::withTrashed()->where("INDEX", $index)->first();
      }

       return static::where("INDEX", $index)->first();
  }  

    // Determins if the passed $user is an owner of this model or not
    // returns Boolean
    // Over ride in any particular class to change the logic for that class.
    public function hasOwner($user){
        if(isset($this->KEY)){
           return $user->KEY === $this->KEY;
        }
        return false;
    }

    public static function last($withTrashed = false){
        if($withTrashed) {
            return static::withTrashed()->orderBy('id','DESC')->first();
        }

        return static::orderBy('id','DESC')->first();
    }

}
