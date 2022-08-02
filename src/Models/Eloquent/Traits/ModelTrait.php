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

    public static function search($input){
        $builder = new static;

        if(isset($input->TRADE_TITLES) && isset($input->CENTERPOINT_TITLES) && $input->TRADE_TITLES === 'on' && $input->CENTERPOINT_TITLES === 'on'){
            //do nothing if they are both set and both equal 'on'
        }else{
            if(isset($input->TRADE_TITLES) && $input->TRADE_TITLES === 'on') $builder = $builder->where('INVNATURE','TRADE');
            if(isset($input->CENTERPOINT_TITLES) && $input->CENTERPOINT_TITLES === 'on') $builder = $builder->where('INVNATURE','CENTE');
        }

        if(isset($input->minimum_price) && $input->minimum_price != false && $input->minimum_price != null) $builder = $builder->where('LISTPRICE',">=", $input->minimum_price);
        if(isset($input->maximum_price) && $input->maximum_price != false && $input->maximum_price != null) $builder = $builder->where('LISTPRICE',"<=", $input->maximum_price);

        if(isset($input->minimum_flatprice) && $input->minimum_flatprice != false && $input->minimum_flatprice != null) $builder = $builder->where('FLATPRICE',">=", $input->minimum_flatprice);
        if(isset($input->maximum_flatprice) && $input->maximum_flatprice != false && $input->maximum_flatprice != null) $builder = $builder->where('FLATPRICE',"<=", $input->maximum_flatprice);

        if(isset($input->after_pubdate) && $input->after_pubdate != false && $input->after_pubdate != null) $builder = $builder->where('PUBDATE',">=", $input->after_pubdate);
        if(isset($input->before_pubdate) && $input->before_pubdate != false && $input->before_pubdate != null) $builder = $builder->where('PUBDATE',"<=", $input->before_pubdate);

        if(isset($input->TITLE) && $input->TITLE != false && $input->TITLE != null && $input->TITLE != '') $builder = $builder->where('TITLE',"LIKE", '%'.$input->TITLE.'%');
        if(isset($input->AUTHORKEY) && $input->AUTHORKEY != false && $input->AUTHORKEY != null && $input->AUTHORKEY != '') $builder = $builder->where('AUTHORKEY','LIKE', '%'.$input->AUTHORKEY.'%');
        if(isset($input->ISBN) && $input->ISBN != false && $input->ISBN != null && $input->ISBN != '') $builder = $builder->where('ISBN','LIKE', '%'.$input->ISBN.'%');
        
        $values = collect([]);

        foreach($input->genres AS $genre){    
            if(isset($genre->checked) && $genre->checked === 'on'){
                $values->push($genre->SCAT);
            }
        }
            if($values->count() > 0) $builder = $builder->whereIn('SCAT', $values);

        if( str_contains(static::class, "Inventories") ){
            $builder = $builder->orderBy('PUBDATE','DESC');
        }
        
        return $builder;
    }

}
