<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits;

trait GraphqlArgsEloquentTrait {

    public static function graphql($args = []){

        $query = new static;

        if(isset($args["first"])){
            $first = $args["first"];
        }else{
            $first = 10;
        }

        if(isset($args["page"])){
            $page = $args["page"];
        }else{
            $page = 1;
        }

        if(isset($args["filter"])){
            foreach($args["filter"] AS $key=>$v){
                if(strpos($v, "_") !== false){
                    $f = explode("_",$v,2);
                } else{
                    $f[0]="=";
                    $f[1]=$v;
                }
                
                $val = trim($f[1]);
                if($val === ""){$val = null;}
                if($val === "TRUE"){$val = true;}
                if($val === "true"){$val = true;}
                if($val === "FALSE"){$val = false;}
                if($val === "false"){$val = false;}

                if($val === null){
                  $query = $query->whereNull($key);
                }else{
                  $query = $query->where($key,$f[0],$val);
                }
                
            }

        }
            return $query;
    }
}