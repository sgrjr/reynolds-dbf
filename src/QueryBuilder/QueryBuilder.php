<?php namespace Sreynoldsjr\ReynoldsDbf\QueryBuilder;

use Sreynoldsjr\ReynoldsDbf\QueryBuilder\DataResults;
use Sreynoldsjr\ReynoldsDbf\Helpers\Compare;
use Sreynoldsjr\ReynoldsDbf\Helpers\StringHelper;
use Sreynoldsjr\ReynoldsDbf\QueryBuilder\QueryParameters;
use Sreynoldsjr\ReynoldsDbf\Interface\DbfQueryBuilderInterface;
use Sreynoldsjr\ReynoldsDbf\Models\Table;
use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\GraphqlArgsTrait;

class QueryBuilder extends IlluminateQueryBuilder implements DbfQueryBuilderInterface {
	
	use GraphqlArgsTrait;

	public $columns;
	protected $parameters;
	protected $writable;
	private $data;
	protected $children;
	public $props;

	public function __construct(IlluminateQueryBuilder $builder){

		$this->columns = false;
		$this->writable = false;
		$this->children = collect([]);
		$this->props = collect([]);
		$this->parameters = new QueryParameters();
		$this->initData();
	}

	 /**
     * Set the affected Eloquent model and instance ids.
     *
     * @param  class-string<TModel>  $model
     * @param  array<int, int|string>|int|string  $ids
     * @return $this
     */
    public function setModel($model, $ids = [])
    {
        $this->model = $model;
        $this->ids = Arr::wrap($ids);
        $this->message = "Gonna read me some dbf.";
        return $this;
    }

	private function initData(){
		$this->data = new DataResults();
		return $this;
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
	
	public function getData(){
		return $this->data;
	}

	public function getParameters(){
		return $this->parameters->all();
	}

	public function reduceColumns($array_of_property_names){
		$this->columns = array_diff($this->columns, $array_of_property_names);
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

public function truncateRecords(){
	$this->data->reset();
	return $this;
}

public function addDataRecord($record, $isList = false, $lists = false){

	if($isList){
		foreach($record->toArray() AS $r){
			$this->data->addRecord($record, $lists);
		}
	}else{
		$this->data->addRecord($record, $lists);
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
		return Compare::test($record, $this->parameters);
	}

    public function all($columns=['*']) {
        $this->parameters->setPerPage(999999999);
        $this->parameters->setPage(1);
        return $this->get(true);
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

	public function setColumns($columns = false){
		$this->model->t()->open();
		$this->props['columns'] = [];

		if($columns !== false && count($columns) > 0){
			foreach($this->model->t()->getColumns() AS $column){
				if(in_array($column->name, $columns)){
					$this->props['columns'][] = $column->toArray();
				}
			}
		}else{
			foreach($this->model->t()->getColumns() AS $column){
				$this->props['columns'][] = $column->toArray();
			}
		}


		$this->model->t()->close();
		return $this;
	}

	public function getColumns(){
		if(!isset($this->props["columns"])){$this->setAllColumns();}
		return $this->props['columns'];
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
    	$key = StringHelper::camelCase($key);

    	$n = "get" . ucfirst($key);

    	if(method_exists($this, $n)){
	       return $this->$n();
        }else if($this->props->has($key)) {
           return $this->props[$key];
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $key .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
        
    }

public function source(){
    return 'dbf';
}

public function with($relations, $callback = null){
	$this->children->push($relations);
	return $this;
}

/**
 * Add subselect queries to count the relations.
 *
 * @param  mixed  $relations
 * @return $this
 */
public function withCount($relations)
{
    return $this->withAggregate(is_array($relations) ? $relations : func_get_args(), '*', 'count');
}

 /* Add subselect queries to include an aggregate value for a relationship.
     *
     * @param  mixed  $relations
     * @param  string  $column
     * @param  string  $function
     * @return $this
     */

    public function withAggregate($relations, $column, $function = null)
    {
        if (empty($relations)) {
            return $this;
        }

        if (is_null($this->query->columns)) {
            $this->query->select([$this->query->from.'.*']);
        }

        $relations = is_array($relations) ? $relations : [$relations];

        foreach ($this->parseWithRelations($relations) as $name => $constraints) {
            // First we will determine if the name has been aliased using an "as" clause on the name
            // and if it has we will extract the actual relationship name and the desired name of
            // the resulting column. This allows multiple aggregates on the same relationships.
            $segments = explode(' ', $name);

            unset($alias);

            if (count($segments) === 3 && Str::lower($segments[1]) === 'as') {
                [$name, $alias] = [$segments[0], $segments[2]];
            }

            $relation = $this->getRelationWithoutConstraints($name);

            if ($function) {
                $hashedColumn = $this->getQuery()->from === $relation->getQuery()->getQuery()->from
                                            ? "{$relation->getRelationCountHash(false)}.$column"
                                            : $column;

                $wrappedColumn = $this->getQuery()->getGrammar()->wrap(
                    $column === '*' ? $column : $relation->getRelated()->qualifyColumn($hashedColumn)
                );

                $expression = $function === 'exists' ? $wrappedColumn : sprintf('%s(%s)', $function, $wrappedColumn);
            } else {
                $expression = $column;
            }

            // Here, we will grab the relationship sub-query and prepare to add it to the main query
            // as a sub-select. First, we'll get the "has" query and use that to get the relation
            // sub-query. We'll format this relationship name and append this column if needed.
            $query = $relation->getRelationExistenceQuery(
                $relation->getRelated()->newQuery(), $this, new Expression($expression)
            )->setBindings([], 'select');

            $query->callScope($constraints);

            $query = $query->mergeConstraintsFrom($relation->getQuery())->toBase();

            // If the query contains certain elements like orderings / more than one column selected
            // then we will remove those elements from the query so that it will execute properly
            // when given to the database. Otherwise, we may receive SQL errors or poor syntax.
            $query->orders = null;
            $query->setBindings([], 'order');

            if (count($query->columns) > 1) {
                $query->columns = [$query->columns[0]];
                $query->bindings['select'] = [];
            }

            // Finally, we will make the proper column alias to the query and run this sub-select on
            // the query builder. Then, we will return the builder instance back to the developer
            // for further constraint chaining that needs to take place on the query as needed.
            $alias ??= Str::snake(
                preg_replace('/[^[:alnum:][:space:]_]/u', '', "$name $function $column")
            );

            if ($function === 'exists') {
                $this->selectRaw(
                    sprintf('exists(%s) as %s', $query->toSql(), $this->getQuery()->grammar->wrap($alias)),
                    $query->getBindings()
                )->withCasts([$alias => 'bool']);
            } else {
                $this->selectSub(
                    $function ? $query : $query->limit(1),
                    $alias
                );
            }
        }

        return $this;
    }

public function where($column, $comparison = null, $value = null, $boolean = 'AND'){
	$this->parameters->addTest([$column, $comparison, $value, $boolean]);
	return $this;
}

public function whereIn($column, $values, $boolean = 'AND', $not = false ){

	$this->parameters->compareOr();
	foreach($values AS $fv){
		$this->parameters->addTest([$column, "==", $fv, 'OR']);
	}
	
	return $this;
}

public function orderByRaw($sql, $bindings = []){
	$this->data->orderByRaw($sql, $bindings);
    return $this;
}

public function setPage($pageValue){
	$this->parameters->page = $pageValue;
	return $this;
}

public function findByIndex($index, $columns = false){
	return $this->index($index, $columns);
}

public function find($primaryKeyValue, $columns = []){
	$this->parameters
		->setPerPage(1)
		->setPage(1);

	return $this
		->setColumns($columns)
		->where($this->getDbfPrimaryKey(),"===", $primaryKeyValue)
		->first();
}

public function index($index = 0, $columns = false){

		$this->setColumns($columns);

			$details = [];
			$this->model->t()->open();
			$this->model->t()->moveTo($index);
			$record=$this->model->t()->getRecord();

    	foreach($this->columns AS $att){

        	if( in_array($att, $this->children) ){
        		$fn = "get" . ucfirst(strtolower($att)) . "Connection";
                $details[$att] = $this->$fn($record);

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
	   

	   	if(count($this->data->records) >= 1){
			return $this->data->records[0];
	   	}
	   	return $this->data->records;
	}

	public function setData(){ 

		$startIndex = -1;	

		 ini_set('memory_limit','512M');
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->model->t()->open();    	

        while ($record=$this->model->t()->nextRecord() ) {
            $rd = $record->getData($this->parameters->ignoreColumns);

            if($this->test($rd) === true){
            	$this->addDataRecord($rd);
            }
            
            if($this->data->count() >= $this->parameters->limit()){
                break;
            }
        }

        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->model->t()->close();

        unset($dbf);
        unset($bag);
        unset($file);
        unset($rd);

        return $this;
	}
	
	public function get($loadToArray = false){
		$this->setData();
		return $this;		
	}

	public function sortBy($field_name, $sort_type= 0){
		return $this;
	}

	public function isEmpty(){
		return $this->data->data->isEmpty();
	}

	public function count($columns = "*"){
		return $this->data->data->count();
	}

	public function reverse(){
		return $this;
	}

	public function pluck($column, $key = null){
		return $this->data->data->pluck($column, $key);
	}

public function first($columns = false){

	$this->parameters->setPerPage(1);

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

	public function getCountForPagination($columns = ['*'])
    {
        return $this->data->count();
    }


}