<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits;

use Sreynoldsjr\ReynoldsDbf\ErrorMessage; //$message="Success", $extensions=null, $debugMessage=null, $severity="info", $field=null
use Sreynoldsjr\ReynoldsDbf\ErrorMessageExtensions; //$category, $props=[]

trait GraphqlMutationsTrait {

    public static function deleteMutation($user, $args){

        if($user === null){
            $user = request()->user();
        }

        $model = static::withTrashed()->find($args['id']);

        $user->deleteThis($model);

    	return $user;
    }

    public static function restoreMutation($user, $args){

        if($user === null){
            $user = request()->user();
        }

        $model = static::withTrashed()->find($args['id']);
        $user->restoreThis($model);

    	return $user;
    }
   
}
