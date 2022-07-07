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
    public function create(array $attributes = [])
    {
        return tap($this->newModelInstance($attributes), function ($instance) {
            $instance->initialize()->save();
        });
    }

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
     
}
