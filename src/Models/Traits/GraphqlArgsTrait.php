<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Traits;

trait GraphqlArgsTrait {

    public function graphql($args = []){
        //first,page,filter,directive

        if(isset($args["first"])){
            $this->parameters->setPerPage($args["first"]);
        }else if(isset($args["perPage"])){
            $this->parameters->setPerPage($args["perPage"]);
        }else{
            $this->parameters->setPerPage(10);
        }

        if(isset($args["page"])){
            $this->parameters->setPage($args["page"]);
        }else{
            $this->parameters->setPage(1);
        }

            if(isset($args["filter"])){
                foreach($args["filter"] AS $key=>$v){
                    if(strpos($v, "_") !== false){
                        $f = explode("_",$v,2);
                    } else{
                        $f[0]="==";
                        $f[1]=$v;
                    }
                    
                    $val = trim($f[1] ?? '');
                    if($val === ""){$val = null;}
                    if($val === "TRUE"){$val = true;}
                    if($val === "true"){$val = true;}
                    if($val === "FALSE"){$val = false;}
                    if($val === "false"){$val = false;}

                    $this->where($key,$f[0],$val);
                }

            }

        return $this;
    }

}