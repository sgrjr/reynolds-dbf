<?php namespace App\Ask;

use App\Ask\DatabaseType\Config\ConfigTable;
use App\Ask\DatabaseType\XBaseTable;
use Config;
use \Illuminate\Pagination\LengthAwarePaginator;
use App\Ask\AskInterface\AskQueryBuilderInterface;
use Illuminate\Database\Eloquent\Builder;
use App\Ask\QueryBuilder;


class XbaseQueryBuilder extends QueryBuilder implements AskQueryBuilderInterface {

   /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection;

public function source(){
    return 'dbf';
}

public function setTable(){
        $this->table = [];

        foreach($this->model->getSeeds() AS $seed){

        	if($this->writable && $seed["type"] === "dbf"){
		        $table = new XBaseTable($seed["path"], $this->writable);
				$this->table[] = $table;
		    }
        }

    return $this;
}

	public function with($name){
		$this->children->push($name);
		return $this;
	}

	public function where($column, $comparison, $value){
		$this->parameters->tests[] = [$column, $comparison, $value];
		return $this;
	}

	public function setPage($pageValue){
		$this->parameters->page = $pageValue;
		return $this;
	}

public function findByIndex($index, $columns = false){
	return $this->index($index, $columns);
}

public function find($primaryKeyValue){
	return $this
		->setPerPage(1)
		->setPage(1)
		->where($this->model->getDbfPrimaryKey(),"===", $primaryKeyValue)
		->first();
}

public function index($index = 0, $columns = false){

		if($columns === false || count($columns) <= 0){
			$this->setColumns($this->model->getFillable());
		}else{
			$this->setColumns($columns);
		}

		foreach($this->table AS $table){
			$details = [];
			$table->open();
			$table->moveTo($index);
			$record=$table->getRecord();

    	foreach($this->columns AS $att){

        	if( in_array($att, $this->children) ){
        		$fn = "get" . ucfirst(strtolower($att)) . "Connection";
                $details[$att] = $this->model->$fn($record);

        	}else{
        		if($att == "SYNOPSIS"){
        			$obj = $record->getColumns()[4];
					$details[$att] = $record->getMemo($obj);

				}else{
					$details[$att] = $record->getObjectByName($att);
				}
        		
        	}

    	}
	   	$details["INDEX"] = $table->getRecordPos();
	   	$details["DELETED"] = $record->isDeleted();
	   	$table->close();
	   	$this->addDataRecord($details);
	   }

	   	if(count($this->data->records) >= 1){
			return $this->data->records[0];
	   	}
	   	return $this->data->records;
	}

    public function importAndEmpty(){
		
		if($this->parameters->import !== false){
			\Schema::disableForeignKeyConstraints();
			$result = $this->model->insert($this->data->records);
			$this->truncateRecords();
			\Schema::enableForeignKeyConstraints();
		}

		return $this;
	}

	public function setData(){ 

		$startIndex = -1;	
		$resetCounterAt = 500;
		$limit = $this->parameters->perPage * $this->parameters->page;

		 ini_set('memory_limit','512M');
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $file = $this->model->xTable();
        $file->open();    	

        while ($record=$file->nextRecord() ) {
            $rd = $record->getData($this->model->getIgnoreColumns());
            $this->addDataRecord($rd);
            
            if(count($this->data->records) >= $this->getLimit()){
                break;
            }
        }

        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $file->close();

        unset($dbf);
        unset($bag);
        unset($file);
        unset($rd);

        return $this;
	}
	
	public function get($loadToArray = false){
		$this->setData();

		$r = new \stdclass;
		$r->paginatorInfo = $this->data->paginator;
		$r->data = $this->data->records;
		return $r;
		
	}

public function first($columns = false){

	$this->setPerPage(1);

	if(count($this->parameters->tests) < 1){
		if($this->parameters->index < 0 || !$this->parameters->index){$this->parameters->index = 0;}
		return $this->index($this->parameters->index, $columns);
	}else{
		$res = $this->get(true)->data;
		if(isset($res[0])){
			return $res[0];
		}else{
			return null;
		}		
	}
	
}

public function count(){
	return $this->get()->paginator->count;
}

public function getColumns(){


	return $this->table[0]->getColumns();
}

public function flush(){
	$this->data->records = null;
	unset($this->data->records);
	$this->data->records = [];
	return $this;
}

    public function importAll(){
		ini_set('memory_limit','3000M');

		foreach($this->table AS $table){
			$table->open();
			$table->importAll();
			$table->close();
		}
		
		return true;

    }

public function fromGraphql($is_always_null_i_think, $options, $request){
	//$filter = $options["filter"]; //first, page, filter:model attributes, directive
	$this->setUser($request->user())->graphqlArgs($options);
	return $this;
}

}