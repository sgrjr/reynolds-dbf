<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Config, DB, Schema;

use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\ModelTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\ManageTableTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\PresentableTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\ODBCDTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\DbfModelTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\GraphqlArgsEloquentTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits\GraphqlMutationsTrait;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\GetHeadersAttributeTrait;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\SetDefaultsTrait; //requires function initialize()
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Table;

class BaseModel extends Model
{
	use DbfModelTrait, GraphqlArgsEloquentTrait, ManageTableTrait, ModelTrait, PresentableTrait, ODBCDTrait, GraphqlMutationsTrait, GetHeadersAttributeTrait, SetDefaultsTrait;

    protected $indexes = [];

     public function graphqlQuery($_, $vars){

      if(isset($vars['dbf']) && $vars['dbf'] === true){
        //query the dbf files directly
        $perPage = isset($vars['first'])? $vars['first']:10;
        $columns = isset($vars['columns'])? $vars['columns']:[];
        $pageName = isset($vars['pageName'])? $vars['pageName']:15;
        $page = isset($vars['page'])? $vars['page']:1;
        
        $result = (new static)->dbf()->graphql($vars)->paginate($perPage,$columns,$pageName,$page);
        return $result;
      }else{
        // query mysql
        $result = (new static)->graphql($vars);
        
        return $result;
      }
    }

    public function graphqlDBFQuery($_, $vars){

        $perPage = isset($vars['first'])? $vars['first']:10;
        $columns = isset($vars['columns'])? $vars['columns']:[];
        $pageName = isset($vars['pageName'])? $vars['pageName']:15;
        $page = isset($vars['page'])? $vars['page']:1;

        return (new static)->dbf()->graphql($vars)->paginate($perPage,$columns,$pageName,$page);
    }

      public static function findFirstByFilter($_, $args){

        $args['first'] = 1;
        $args['page'] = 1;

        if(isset($args['dbf']) && $args['dbf'] === true){
             return (new static)->dbf()->graphql($args)->first();
        }
        // This is a hack as there is where behavior with the default withoutTrashed()
        // as it modifes the query to 'deleted_at = null' which always fails
        // The query must be 'deleted_at IS NULL';
         return static::graphql($args)->orderBy('id','DESC')->first();
  }

    /**
     * Save a new model and return the instance.
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model|$this
     */
    /*
    public function create(array $attributes = [])
    {
        return tap($this->newModelInstance($attributes), function ($instance) {
            $instance->initialize()->save();
        });
    }
*/
        /**
     * Create and return an un-saved model instance.
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public function make(array $attributes = [])
    {
        return $this->newModelInstance($attributes)->initialize();
    }
     
    public function database(){
        if(!$this->db) $this->db = new Table($this);
        return $this->db;
    }

    public function getDatabaseAttribute(){
        return $this->dbf()->database;
    }

    public static function unique($column)
    {
        return (new static)->select($column)->distinct()->get();
    }

    /**
     * Overwriting the provided method in Laravel Eloquent
     * Dynamically retrieve attributes on the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        $value = $this->getAttribute($key);
        if(is_object($value) && str_contains(get_class($value), 'DataEntry') ) return $value->value;
        return $value;
    }

    protected static function boot()
    {
        parent::boot();
 
        static::created(function ($model) {
            $dbf = $model->dbf()->save();
            $model->INDEX = $dbf->INDEX;
            $model->save();
        });

        static::saved(function ($model) {
            if($model->INDEX === "" || $model->INDEX === null) {
                $dbf = $model->dbf()->save();
                $model->INDEX = $dbf->INDEX;
                $model->save();
            }
        });


        static::deleting(function ($model) {
            $dbf = $model->dbf()->delete();
        });

      static::restored(function ($model) {
            $model->dfb()->restore();
        });
  

        /*
        creating and created: sent before and after records have been created.
        updating and updated: sent before and after records are updated.
        saving and saved: sent before and after records are saved (i.e created or updated).
        deleting and deleted: sent before and after records are deleted or soft-deleted.
        restoring and restored: sent before and after soft-deleted records are restored.
        retrieved: sent after records have been retrieved.
        */
    }
}
