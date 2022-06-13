<?php namespace Sreynoldsjr\ReynoldsDbf\Models;

use Sreynoldsjr\ReynoldsDbf\Models\Table;
use Sreynoldsjr\ReynoldsDbf\Helpers\Helper;
use stdclass;

class Model {

	private $props;
	public $data;

	public function __construct($file_name, $skip_memo = true, $writable = false){
		$this->props = [
			"name" => $file_name,
			"skip_memo" => $skip_memo,
			"writable" => $writable
		];

		$this->data = [];
	}

	private function setTable(){
		$this->props['table'] = new Table($this->name, $this->skip_memo, $this->writable);
		return $this;
	}

	private function getTable(){
		if(!isset($this->props["table"])){$this->setTable();}
		return $this->props['table'];
	}

	public function setColumns(){
		$this->table->open();
		$this->props['columns'] = [];

		foreach($this->table->getColumns() AS $column){
			$this->props['columns'][] = $column->toArray();
		}

		$this->table->close();
		return $this;
	}

	public function getColumns(){
		if(!isset($this->props["columns"])){$this->setColumns();}
		return $this->props['columns'];
	}

	public function getName(){
		return $this->props['name'];
	}

	public function getSkipMemo(){
		return $this->props['skip_memo'];
	}

	public function getWritable(){
		return $this->props['writable'];
	}

    public function __set($name, $value)
    {	
        $this->props[$name] = $value;
    }

	public function __get($key)
    {
    	$key = Helper::camelCase($key);

    	$n = "get" . ucfirst($key);

    	if(method_exists($this, $n)){
	       return $this->$n();
        }else if(array_key_exists($key, $this->data)) {
           return $this->data[$key];
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