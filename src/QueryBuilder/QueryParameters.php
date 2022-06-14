<?php namespace Sreynoldsjr\ReynoldsDbf\QueryBuilder;

use Sreynoldsjr\ReynoldsDbf\Helpers\StringHelper;

class QueryParameters {

	public $props;

	public function __construct(Array $args = []){
		
    $parameters = new \stdclass;
  	$parameters->tests = [];
    $parameters->ignoreColumns = [];
    $parameters->selectColumns = ['*'];
  	$parameters->testsComparison = "AND";
  	$parameters->page = 1;
  	$parameters->perPage = 5;
    $parameters->skipModel = false;
    $parameters->order = new \stdclass;
    $parameters->order->column = "INDEX";
    $parameters->order->direction = "ASC";
    $parameters->lists = false;
    $parameters->index = false;
    
    if($args !== false){
      foreach($args AS $k=>$v){

        if($k === "filters"){
          foreach($v AS $k=>$v){
            $val = explode("_", $v, 2);

            if(is_array($val) && isset($val[1]) ){
              $comp = $val[0];
              $value = $val[1];
            }else{
              $comp = "LIKE";
              $value = $val[0];
            }

            $parameters->tests[] = [$k, $comp, $value];
          }
        }else{
          $parameters->$k = $v;
        }
        
      }
      
    }
    	$this->props = $parameters;
	}

	public function getLimit(){
		return $this->props->page * $this->props->perPage;
	}

	public function limit(){
		return $this->getLimit();
	}

	public function all(){
		return $this->props;
	}


	public function setIndex($newIndex){
		$this->props->index = $newIndex;
		return $this;
	}

	public function setParameter($parameterName, $parameterValue){
		$this->props->$parameterName = $parameterValue;
		return $this;
	}

	public function setTests($testsValue){
		$this->props->tests = $testsValue;
		return $this;
	}

	public function addTest($testValue){
		$this->props->tests[] = $testValue;
		return $this;
	}

	public function setPerPage($perPageValue){
		$this->props->perPage = $perPageValue;
		return $this;
	}

	public function setPage($pageValue){
		$this->props->page = $pageValue;
		return $this;
	}

	public function setLists($lists = false){
		$this->props->lists = $lists;
		return $this;
	}

	public function orderBy(String $column, $direction = "ASC"){
        $this->props->order->column = $column;  
        $this->props->order->direction = $direction;  
        return $this;
	}

	public function compareOr(){
		$this->props->testsComparison = "OR";
		return $this;
	}

	public function compareAnd(){
		$this->props->testsComparison = "AND";
		return $this;
	}

	public function __set($name, $value)
    {	
        $this->props[$name] = $value;
    }

	public function __get($key)
    {
    	$key = StringHelper::camelCase($key);

    	$n = "get" . ucfirst($key);

    	if(method_exists($this, $n)){
	       return $this->$n();
        }else if(isset($this->props->$key)) {
           return $this->props->$key;
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $key .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
        
    }

}
