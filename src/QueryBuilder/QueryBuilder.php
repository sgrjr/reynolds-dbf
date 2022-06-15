<?php namespace Sreynoldsjr\ReynoldsDbf\QueryBuilder;

use Sreynoldsjr\ReynoldsDbf\QueryBuilder\DataResults;
use Sreynoldsjr\ReynoldsDbf\Helpers\Compare;
use Sreynoldsjr\ReynoldsDbf\Helpers\StringHelper;
use Sreynoldsjr\ReynoldsDbf\QueryBuilder\QueryParameters;
use Sreynoldsjr\ReynoldsDbf\QueryBuilder\PaginatorInfo;
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
	public $database;
	public $model;

	public function __construct($model, $database){
		$this->model = $model;
		$this->database =& $database;
		$this->columns = false;
		$this->writable = false;
		$this->children = collect([]);
		$this->props = collect([]);
		$this->parameters = new QueryParameters();
		$this->initData();
	}
  
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
// $record is an array of values
public function addDataRecord($record){
	$this->data->addRecord($this->model->make($record));
	return $this;
}

	public function test($record){
		return Compare::test($record, $this->parameters);
	}

    public function all($columns=['*']) {
        $this->parameters->setPerPage(999999999);
        $this->parameters->setPage(1);
        return $this->get($columns);
    }

    public function last($columns=['*']) {
        $this->parameters->setPerPage(1);
        $this->parameters->setPage(1);
        $this->parameters->setIndex($this->database->getRecordCount()-1);
        return $this->get($columns)->last();
    }

    public function index($index, $columns = ['*']) {
        $this->parameters->setPerPage(1);
        $this->parameters->setPage(1);
        $this->parameters->setIndex($index);
        return $this->get($columns)->first();
    }

    public function first($columns = false){
    	$this->parameters->setPerPage(1);
        $this->parameters->setPage(1);
        return $this->get($columns)->first();
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
		$this->database->open();
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


	public function setData($columns = ['*']){ 

		ini_set('memory_limit','512M');
		$this->parameters->setSelect($columns);
        $this->database->open(); 

        if($this->parameters->index !== false){
        	$record = $this->database->moveTo($this->parameters->index);

        	// If a starting index was given, The limit is set to 1 and the record passes the tests
        	// then add this record and RETURN out of this function. 
        	$rd = $record->getData($this->parameters->ignoreColumns);
        	if($this->parameters->limit() === 1 && $this->test($rd)){
        		$this->addDataRecord($rd);
        		return $this;
        	}
        }   	

        while ($record=$this->database->nextRecord() ) {
            $rd = $record->getData($this->parameters->ignoreColumns);

            if($this->test($rd) === true){
            	$this->addDataRecord($rd);
            }
            
            if($this->data->count() >= $this->parameters->limit()){
                break;
            }
        }

        $this->database->close();

        unset($dbf);
        unset($bag);
        unset($file);
        unset($rd);

        return $this;
	}
	
	public function get($columns = ["*"]){
		$this->setData($columns);
		return $this->data->data;		
	}
	//$perPage = 15, $columns = [...], $pageName = 'page', $page = null)
	public function paginate($perPage = 15, $columns = [], $pageName = 'page', $page = null){ //not passing along $pageName for now
		$this->page(1)->perPage(999999)->setData($columns);
		return PaginatorInfo::get(["items"=>$this->data->data, "perPage"=>$perPage, "page"=> $page]);	
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

	public function getCountForPagination($columns = ['*'])
    {
        return $this->data->count();
    }

    //Parameters Convenience Functions

    public function page(Int $page)
    {
    	$this->parameters->setPage($page);
    	return $this;
    }

    public function perPage(Int $page)
    {
    	$this->parameters->setPerPage($page);
    	return $this;
    }
    public function limit($limit)
    {
    	$this->parameters->setPage(1);
    	$this->parameters->setPerPage($limit);
    	return $this;
    }

    public function setIndex($index){
    	$this->parameters->setIndex($index);
    	return $this;
    }
}