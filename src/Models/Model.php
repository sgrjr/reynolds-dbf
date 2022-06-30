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
use Illuminate\Database\Eloquent\Concerns\HasAttributes;
use Sreynoldsjr\ReynoldsDbf\QueryBuilder\QueryConnectionResolver as Resolver;
use Sreynoldsjr\ReynoldsDbf\QueryBuilder\DbfConnection;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\MagicFunctionsTrait;

class Model {

	use DbfTableTrait,
        DbfModelTrait,
        DbfValidationTrait,
        HasAttributes,
        MagicFunctionsTrait;
  /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'dbf';
    public $source;
    public $sourceTable;
    public $timestamps = false;
    protected $columns = [];
    protected $relations = [];
    public $builder = null;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = [];

    /**
     * The relationship counts that should be eager loaded on every query.
     *
     * @var array
     */
    protected $withCount = [];

    /**
     * Indicates whether lazy loading will be prevented on this model.
     *
     * @var bool
     */
    public $preventsLazyLoading = false;

    /**
     * The number of models to return for pagination.
     *
     * @var int
     */
    protected $perPage = 15;

    /**
     * Indicates if the model exists.
     *
     * @var bool
     */
    public $exists = false;

    /**
     * Indicates if the model was inserted during the current request lifecycle.
     *
     * @var bool
     */
    public $wasRecentlyCreated = false;

    /**
     * Indicates that the object's string representation should be escaped when __toString is invoked.
     *
     * @var bool
     */
    protected $escapeWhenCastingToString = false;

    /**
     * The connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected static $resolver;

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected static $dispatcher;

    /**
     * The array of booted models.
     *
     * @var array
     */
    protected static $booted = [];

    /**
     * The array of trait initializers that will be called on each new instance.
     *
     * @var array
     */
    protected static $traitInitializers = [];

    /**
     * The array of global scopes on the model.
     *
     * @var array
     */
    protected static $globalScopes = [];

    /**
     * The list of models classes that should not be affected with touch.
     *
     * @var array
     */
    protected static $ignoreOnTouch = [];

    /**
     * Indicates whether lazy loading should be restricted on all models.
     *
     * @var bool
     */
    protected static $modelsShouldPreventLazyLoading = false;

    /**
     * The callback that is responsible for handling lazy loading violations.
     *
     * @var callable|null
     */
    protected static $lazyLoadingViolationCallback;

    /**
     * Indicates if broadcasting is currently enabled.
     *
     * @var bool
     */
    protected static $isBroadcasting = true;

    /**
     * The name of the "created at" column.
     *
     * @var string|null
     */
    const CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
     *
     * @var string|null
     */
    const UPDATED_AT = 'updated_at';

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */

public function __construct(array $attributes = []){
    $this->attributes = $attributes;
	$file = config('reynolds-dbf.files')[$this->getTable()];
    $this->database = new Table(config('reynolds-dbf.root_paths')[$file[1]] . DIRECTORY_SEPARATOR . $file[0]);
    $this->builder = new QueryBuilder($this, $this->database);
}

public function getTable(){
    return $this->table;
}

public function getColumnsAttribute(){
    return $this->database->getColumns();
}

public function graphql($args){
    return $this->builder->graphql($args);
}

public function asObject(){
    $this->builder->asObject();
    return $this;
}

public function last($columns = ['*']){
    return $this->builder->last($columns);
}

public function get($columns = ['*']){
    return $this->builder->get($columns);
}

public static function all($columns = ['*'])
{
    return (new static)->builder->all($columns);
}

public function first($columns = ['*'])
{
    return $this->builder->first($columns);
}

public function paginate($perPage = 15, $columns = [], $pageName = 'page', $page = 1)
{
    return $this->builder->paginate($perPage, $columns, $pageName, $page);
}

public function where($field, $operator, $value)
{
    $this->builder->where($field, $operator, $value);
    return $this;
}

public function limit(Int $limit)
{
    $this->builder->limit($limit);
    return $this;
}

public function page(Int $page)
{
    $this->builder->page($page);
    return $this;
}

public function perPage(Int $page)
{
    $this->builder->perPage($page);
    return $this;
}

public function findByIndex($index, $columns = ['*'])
{
    return $this->builder->findByIndex($index, $columns);
}

public function save(array $options = []){
    //not sure how to implement the $options array yet
    $result = $this->database->save($this->attributes);

    foreach($result AS $key=>$att){
        $this->$key = $att;
    }

    return $this;
}

public function toArray(){
    $x = $this->attributes;
    unset($x['database']);
    return $x;
}

public function meta(){
    if (!$this->database->isOpen()) $this->database->open();
    $record = $this->database->moveTo($this->attributes['INDEX']);
    $meta = $record->meta();
    if ($this->database->isOpen()) $this->database->close();
    return $meta;
}

public static function count(){
    return (new static)->database->count();
}

public static function saveMany(array $items = []){

    foreach ($items AS $item){
        $model = static::create($item)->save();
    }

    return true;
}

public static function create($attributes = []){
    $model = new static($attributes);
    return $model->save(); 
}

public static function make($attributes = []){
    return new static($attributes);
}

public function delete(){
   $record = $this->database->delete($this->INDEX);
   return $this->copyFrom($record);
}

public function restore(){
   $record = $this->database->restore($this->INDEX);
   return $this->copyFrom($record);
}

public function copyFrom($attributes){
     foreach($attributes AS $key=>$att){
        $this->$key = $att;
    }
    return $this;
}

/**
 * Get the value indicating whether the IDs are incrementing.
 *
 * @return bool
 */
public function getIncrementing()
{
    return $this->incrementing;
}

/**
 * Determine if the model uses timestamps.
 *
 * @return bool
 */
public function usesTimestamps()
{
    return $this->timestamps;
}

public static function query(){
    $model = new static;
    $model->builder->setIndex(false);
    return $model;
}

public function isDeleted(){
    return $this->deleted_at != null || $this->deleted_at != false;
}

public function trashed(){
    return $this->isDeleted();
}

public function isAvailable(){
    return $this->database->isAvailable();
}

public function relationLoaded($key)
{
    return array_key_exists($key, $this->relations);
}

public static function loop($callback, $startIndex=-1, $limit=1){
    $ctr = 0;
    $db = (new static)->database;
    $db->open();

    $db->moveTo($startIndex);

    //iterate through the records
    while ($record=$db->nextRecord()) { 
       $callback($record);
        $ctr++;
        if($ctr >= $limit){break;}
    }

    //close the file
    $db->close();
}

}