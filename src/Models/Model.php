<?php namespace Sreynoldsjr\ReynoldsDbf\Models;

use Sreynoldsjr\ReynoldsDbf\Models\Table;
use Sreynoldsjr\ReynoldsDbf\Helpers\Helper;
use stdclass;
use Sreynoldsjr\ReynoldsDbf\QueryBuilder\QueryBuilder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Query\Builder;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\DbfTableTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\DbfModelTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\DbfValidationTrait;

class Model extends EloquentModel {

	use DbfTableTrait;
	use DbfModelTrait;
	use DbfValidationTrait;

protected $connection = 'dbf';
public $source;
public $sourceTable;
public $props = [];

public function __construct(array $attributes = []){
	parent::__construct($attributes);
	$file = \Config::get('reynolds-dbf.files')[$this->getTable()];
	$this->setSource(config('reynolds-dbf.root_paths')[$file[1]] . DIRECTORY_SEPARATOR . $file[0]);
}

public function newEloquentBuilder($query): Builder
    {
        return new QueryBuilder($query);
    }

  public function setSource($file_name){
  	$this->source = $file_name;
  	return $this;
  }

  public function getSource(){
  	return $this->source;
  }

 	 public function setSourceTable(){
        $this->sourceTable = new Table($this->source);
        return $this;
    }

    public function getSourceTable(){
        if(!isset($this->sourceTable)){$this->setSourceTable();}
        return $this->sourceTable;
    }

    public function t(){
    	if(!isset($this->sourceTable)){$this->setSourceTable();}
    	return $this->sourceTable;
    }

    public function graphql($args){
        $result = static::query()->graphql($args);
        return $result;
    }

    public static function query()
    {
        return (new static)->newQuery();
    }

    public function newQuery()
    {
        return $this->newEloquentBuilder(
            $this->newBaseQueryBuilder()
        )->setModel($this);
    }

     public function importAll(){
		ini_set('memory_limit','3000M');

		$this->table->open();
		$this->table->importAll();
		$this->table->close();
		
		return $this;

    }

    /**
     * Get all of the models from the database.
     *
     * @param  array|string  $columns
     * @return \Illuminate\Database\Eloquent\Collection<int, static>
     */
    public static function all($columns = ['*'])
    {
    	$x = new static;
    	$x->setSourceTable();

        return $x->query()->all(
            is_array($columns) ? $columns : func_get_args()
        );
    }

}