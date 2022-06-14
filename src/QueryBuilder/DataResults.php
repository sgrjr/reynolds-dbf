<?php namespace Sreynoldsjr\ReynoldsDbf\QueryBuilder;

class DataResults {
	public function __construct(){
		$this->data = collect([]);
		$this->lists = [];
	}

	public function addRecord($record, $lists = false){
		$this->data->push($record);

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
	
	public function implode($use_this_glue = ', '){
		$this->data->implode($use_this_glue);
		return $this;
	}

	//This method does not map exactly to the eloquent version
	//a check must be made when using as it has a different architecture

	public function orderByRaw(String $field, $list){
		/*$this->data->sortKeysUsing(function($a, $b){
			$a = preg_replace('@^(a|an|the) @', '', $a);
		    $b = preg_replace('@^(a|an|the) @', '', $b);
		    return strcasecmp($a, $b);
		});*/

		$new_list = collect([]);
		foreach($list as $item){
			$new_list->push($this->find($field,$item));
		}

		$this->data = $new_list;
    	return $this;
	}

	public function find(String $field, $value){

		foreach($this->data as $item){
			if($item[$field] === $value){
				return $item;
			}
		}

		return null;
	}


	public function count(){
		return $this->data->count();
	}

	public function done(){
		return $this;
	}

	public function renderLinks(){
		return "<!--<h2>links</h2>-->";
	}

	public function reset(){
		$this->data = collect([]);
		return $this;
	}

	public function sortBy($field_name, $sort_type= 0){
		$this->data->sortBy($field_name, $sort_type);
		return $this;
	}

	public function reverse(){
		$this->data->reverse();
		return $this;
	}

	public function pluck($column, $key = null){
		return $this->data->pluck($column, $key);
	}
}
