<?php namespace App\Ask;

class Question {
		public function __construct(Array $questionArray){
		
            foreach ($questionArray as $key => $value)
            {
                    // Add the value to the object
                    $this->{$key} = $value;
            }
	}
}

class QuerySchema {
	public function __construct(){
		$this->models = collect([]);
	}

	public function addModel($model){
		$this->models->push($model);
	}

	public function __get($name)
    {

    	$prop = $this->models->filter(function($model) use ($name){
    			return $model->name == $name;
    	})->first();

        if ($prop !== null ) {
            return $prop;
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
        
    }
}

class SchemaModel {
	public function __construct($name){
		$this->name = $name;
	}

	public function property($name, $resolve){
		if(is_object($resolve)){
			$this->$name = $resolve;
			$this->$name->value = false;
		}else{
			$this->$name = new \stdclass;
			$this->$name->value = $resolve;
		}
		
	}

}

class Query {
	public function __construct(){
		$this->initSchema();
		$this->questions = collect([]);
		$this->q = new \stdclass;

	}

	public function initSchema(){
		$this->schema = new QuerySchema();

		$orders = new SchemaModel([
			"name"=>"book",
			"root"=> new \stdclass,
			"attributes" => []
		]);

		$this->schema->addModel($orders);


		return $this;
	}

	public function ask($question){
		$question = new Question($question);
		$this->questions->push($question);
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
		$this->paginator->pages = (int) number_format(ceil($this->paginator->total/$this->paginator->perPage),0);
		return $this;
	}

	public function listNouns(){

			foreach($this->questions AS $q ){

				$noun = $q->noun;

				if(isset($this->q->$noun)){
					$this->q->$noun->push($q);
				}else{
					$this->q->$noun = collect([$q]);
				}
				
			}
		return $this;
	}

	public function get(){
		$this->listNouns()
			//->calcCount()
			//->setHeaders()
			//->calcLinks()
		;
		return $this;
	}

}
