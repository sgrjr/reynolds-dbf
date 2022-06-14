<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Traits;

trait GraphqlArgsEloquentTrait {

    public function graphql($args = null){

        if(isset($options["first"])){
            $first = $options["first"];
        }else{
            $first = 10;
        }

        if(isset($options["page"])){
            $page = $options["page"];
        }else{
            $page = 1;
        }

        $model = (new static())->orderBy('id','DESC');

            if(isset($args["filter"])){
                foreach($args["filter"] AS $key=>$v){
                    if(strpos($v, "_") !== false){
                        $f = explode("_",$v,2);
                    } else{
                        $f[0]="==";
                        $f[1]=$v;
                    }
                    
                    $val = trim($f[1]);
                    if($val === ""){$val = null;}
                    if($val === "TRUE"){$val = true;}
                    if($val === "true"){$val = true;}
                    if($val === "FALSE"){$val = false;}
                    if($val === "false"){$val = false;}

                    $model->where($key,$f[0],$val);
                }

            }

        return $model;
    }
}