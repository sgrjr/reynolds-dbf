<?php namespace App\Ask;

use App\Ask\DatabaseType\Config\ConfigTable;
use App\RecordDetails;
use App\Helpers\Compare;
use Config;
use App\Ask\AskInterface\AskQueryBuilderInterface;

class DataResults {
	public function __construct($model){
		$this->model = $model;
		$this->records = [];
		$this->headers = collect([]);
		$this->initPaginator();
		$this->lists = [];
	}

	public function initPaginator(){
		$this->paginator = new \stdclass;
		$this->paginator->currentPage = 1;
        $this->paginator->perPage = 5;
        $this->paginator->index = 0;
        $this->paginator->total = 0;  
        $this->paginator->pages = 0;
        $this->paginator->count = 0;
        $this->paginator->links = [];

		return $this;
	}

	public function addRecord($record, $lists = false){
		$this->records[] = $record;
		
		if($lists !== false){
			foreach($lists AS $list){
				
				if($list === "CAT"){
					$index = \App\Helpers\StringHelper::cleanKey($record[$list]);
				}else{
					$index = $record[$list];
				}

				$this->lists[$list][$index][] = $record;
			}
		}

		if($lists !== false && isset($this->lists["PUBDATE"])){
			krsort($this->lists["PUBDATE"]);
		}
		
		return $this;
	}

	public function updatePaginator($prop, $val){
		$this->paginator->$prop = $val;
		return $this;
	}

	public function calcLinks(){
		//TO DO: Add link creation and update code here sometime
		return $this;
	}

	public function calcCount(){
		$this->paginator->count = count($this->records);
		return $this;
	}

	public function calcPages(){
		$this->paginator->pages = (int) ceil($this->paginator->total/$this->paginator->perPage);
		return $this;
	}

	public function done(){
		return $this;
	}

	public function renderLinks(){
		return "<!--<h2>links</h2>-->";
	}

	public function reset(){
		$this->records = [];
		return $this;
	}

	public function truncate(){
		$this->model->truncate();
		return $this;
	}

}

class QueryBuilder {
	
	protected $columns = false;
	protected $parameters = [];
	protected $writable = false;
	protected $table = [];
	protected $data = false;
	protected $children = [];
	protected $user = null;

	public function __construct(Object $model, $writable = false, $import = false){
		$this->model = $model;
		$this->parameters = \App\Helpers\Misc::defaultParameters();
		$this->writable = $writable;

		$this
			->initData()
			->setTable()
			->import($import);
	}

	public function setUser($user){
		$this->user = $user;
		return $this;
	}

	private function initData(){
		$this->data = new DataResults($this->model);
		return $this;
	}

	public function setRoot($root){
		$this->root = $root;
		return $this;
	}

	public function getLimit(){
		return $this->page * $this->perPage;
	}
	public function setMemo($val){
		$this->memo = $val;
		return $this;
	}

	public function setWritable($val){
		$this->writable = $val;
		return $this;
	}

	public function getMemo(){
		return $this->memo;
	}
	
	public function getTable(){
		return $this->table;
	}

	public function getData(){
		return $this->data;
	}

	public function getParameters(){
		return $this->parameters;
	}

	public function setIndex($newIndex){
		$this->parameters->index = $newIndex;
		return $this;
	}

	public function setParameter($parameterName, $parameterValue){
		$this->parameters->$parameterName = $parameterValue;
		return $this;
	}

	public function setTests($testsValue){
		$this->parameters->tests = $testsValue;
		return $this;
	}

	public function setPerPage($perPageValue){
		$this->parameters->perPage = $perPageValue;
		return $this;
	}

	public function skipModel($trueOrFalse = false){
		$this->parameters->skipModel = $trueOrFalse;
		return $this;
	}

	public function import($table = false){
		$this->parameters->import = $table;
		return $this;
	}


	public function orderBy(String $column, $direction = "ASC"){

        $this->parameters->order->column = $column;  
        $this->parameters->order->direction = $direction;  
        return $this;
	}

	public function graphqlArgs($args = null){
		//first,page,filter,directive

		if(isset($options["first"])){
			$this->setPerPage($options["first"]);
		}else{
			$this->setPerPage(10);
		}

		if(isset($options["page"])){
			$this->setPage($options["page"]);
		}else{
			$this->setPage(1);
		}

            if(isset($args["filter"])){
                foreach($args["filter"] AS $key=>$v){
                    if(strpos($v, "_") !== false){
                        $f = explode("_",$v,2);
                    } else{
                        $f[0]="==";
                        $f[1]=$v;
                    }
                    
                    $val = trim($f[1]);
                    if($val === ""){$val = null;}
                    if($val === "TRUE"){$val = true;}
                    if($val === "true"){$val = true;}
                    if($val === "FALSE"){$val = false;}
                    if($val === "false"){$val = false;}

                    $this->where($key,$f[0],$val);
                }

            }

		return $this;
	}


	public function lists($lists = false){
		$this->parameters->lists = $lists;
		return $this;
	}

	public function reduceColumns($array_of_property_names){
		$this->columns = array_diff($this->columns, $array_of_property_names);
		return $this;
	}

	public function setColumns($array_of_property_names){
		$newColumns = [];

		foreach($array_of_property_names AS $name){
			if(is_object($name) ){
				$newColumns[] = $name;
			}else{
				$newColumns[] = strtoupper($name);
			}
		}

		$this->columns = $newColumns;

		return $this;
	}

public function testConfig($key){

		$tests = false;

		if(in_array($key, $this->columns) ){
			$tests = true;
		}else if(in_array(strtoupper($key), $this->columns) ){
			$tests = true;
		}

		return $tests;

}

public function autoSetColumns($columns){
		if($columns === false || count($columns) <= 0){
			$this->setColumns(array_merge($this->model->getFillable(), $this->children));
		}else{
			$this->setColumns(array_merge($columns, $this->children));
		}
}

public function truncateRecords(){
	$this->data->reset();
	return $this;
}


public function addDataRecord($record, $isList = false, $skipModel = false, $lists = false){


	if($isList){
		foreach($record->toArray() AS $r){
			if($skipModel){
				$this->data->addRecord($record, $lists);
			}else{
				$x = $this->model->make((Array) $r);
				$this->data->addRecord($x, $lists);
			}

		}
	}else{
		if($skipModel){
			$this->data->addRecord($record, $lists);
		}else{
			$model = $this->model->make($record);
			$this->data->addRecord($model, $lists);
		}
		
	}
	
	return $this;
}

public function updatePaginator($total, $lastIndex = false){
	
	    $this->data
	    	->updatePaginator("currentPage", $this->parameters->page)
			->updatePaginator("perPage", $this->parameters->perPage)
			->updatePaginator("index", $lastIndex)
			->updatePaginator("total", $total)
			->done();
	
	return $this;
}

	public function test($record){
		return \App\Helpers\Compare::test($record, $this->parameters);
	}

    public function all($limit = false, $page = false, $columns = false, $skipModel = true){
		if(!$limit){$limit = 999999999;}
        $this->parameters->tests = "all";
        $this->setPerPage($limit);

        if($page !== false){
            $this->setPage($page);
        }
        return $this->skipModel($skipModel)->get(true);
    }

	public function __get($name)
    {

        $n = "get" . ucfirst($name);
        if (method_exists($this, $n)) {
            return $this->$n();
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
        
    }

	 public function convertComparison($comparison){
	
        switch($comparison){
            case "!==":
                return "!=";
                break;
            case "===":
                return "=";
                break;
            case "==":
                return "=";
                break;
            default:
                return $comparison;

        }
    }



}