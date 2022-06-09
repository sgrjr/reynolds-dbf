<?php namespace App\Ask;

use App\Ask\AskInterface\AskQueryBuilderInterface;
use App\Ask\QueryBuilder;

class EloquentQueryBuilder extends QueryBuilder implements AskQueryBuilderInterface {

    public function source(){
        return 'mysql';
    }

    public function setTable(){
       
        $this->table = [\DB::table($this->model->getTable())];
        return $this;
    }

    public function with($arrayOfRelationshipNames){
        return $this;
    }
    
    public function where($column, $comparison, $value){
        $comparison = $this->convertComparison($comparison);
        $column = $this->convertColumn($column);

        $value = str_replace("+", " - ", $value);

        if($comparison === 'LIKE'){
            $value = "%$value%";  
		}


        foreach($this->table AS $key=>$table){
            $this->table[$key] =  $table->where($column, $comparison, $value); 
		}
        
        return $this;
    }
    
    public function setPage($pageValue){
        $this->parameters->page = $pageValue;
        return $this;
    }
    
    public function findByIndex($index, $columns = false){
        
        if($columns !== false){
            $atts = $this->table->where("INDEX", $index)->first($columns);
        }else{
            $atts = $this->table->where("INDEX", $index)->first();
        }
        
        return $this->model->make((Array) $atts);
    }
    
    public function find($primaryKeyValue){
        return $this
            ->where($this->model->getKeyName(), "LIKE",$primaryKeyValue)
            ->setPage(1)
            ->setPerPage(1)
            ->first();
    }

    public function index($index, $columns = false){
        return $this;
    }

    public function orderBy(String $column, $direction = "asc"){

        foreach($this->table AS $key=>$table){
            $this->table[$key] =  $table->orderBy($column, $direction);
		}

        return $this;
	}

    public function get($columns = false){

        $this->autoSetColumns($columns);

        $count_queried = $this->parameters->page * $this->parameters->perPage;    
        $offset = ($this->parameters->page * $this->parameters->perPage) - $this->parameters->perPage;
        
        //$dif = array_diff($this->model->getFillable(), $this->columns);

        $result = [];

        foreach($this->table AS $table){
               $r = $table
                    ->limit($count_queried)
                    ->get($this->columns);

          $result = array_merge($result, $r->toArray());

		}
        $result = collect($result);

        $total = $result->count();
        $this->addDataRecord($result->slice($offset, $this->parameters->perPage), true);

        $lastIndex = null;
        
        $this->updatePaginator($total, $lastIndex);
        
        return $this->data;
    }
    
    public function test($record){}
    
    public function first($columns = false){
        $this->autoSetColumns($columns);

        return $this->model->make((Array) $this->table[0]->first($this->columns));

    }

    private function convertColumn($column){

        $function_name = "getAlternate" . ucfirst(strtolower($column));

        if(function_exists($this->model->$function_name)){
            return $this->model->$function_name();
        }

        return $column;
    }

public function count(){
    return $this->table->count();
}

    public function setColumns($array_of_property_names){
        $this->columns = $array_of_property_names;
        return $this;
    }

}
