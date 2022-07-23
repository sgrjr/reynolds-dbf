<?php namespace Sreynoldsjr\ReynoldsDbf;

use Sreynoldsjr\ReynoldsDbf\Models\Model;
use stdclass;

class ReynoldsDbf
{

    public function __construct(){
        $this->files = [];
        $this->eloquent = [];
        $this->initFiles();
    }

    public function initFiles(){

         $config = config('reynolds-dbf');
        
        if($config['find_dbfs']){

        }else{
            foreach($config['files'] AS $key=>$file){
                $model = "\\Sreynoldsjr\\ReynoldsDbf\\Models\\".ucfirst($key);
                $this->files[$key] = new $model();

                $model2 = "\\Sreynoldsjr\\ReynoldsDbf\\Models\\Eloquent\\".ucfirst($key);
                $this->eloquent[$key] = new $model2();
            }
        }

        return $this;
    }
    /**
     * Multiplies the two given numbers
     * @param int $a
     * @param int $b
     * @return int
     */
    public function multiply($a, $b)
    {
        return $a * $b;
    }

    public static function model($file_key){
        $that = new static();
        return $that->files[$file_key];
    }

    public static function all(){
        $that = new static();
        return $that->files;
    }

    public static function query($query_string){

        $q = json_decode('{"root":"webheads", "filters":[{"f":"KEY","o":"=","v":5}], "page":1, "perPage":5, "props":["KEY",{"root":"webdetail", "filters":[{"f":"ISCOMPLETE","o":"=","v":false}]}]}');
        
        $root = (new static)->model($q->root);
        
        $limit = $q->page * $q->perPage;

        $data = [];

        //open the file to read the header
        $root->t()->open();

        //iterate through the records
        while ($record=$root->t()->nextRecord()) { 
            $data[]=$record->toArray();
            if(count($data) >= $limit){break;}
        }

        //close the file
        $root->t()->close();

        return $data;
    }

    public static function install($force = false){
        $dbf = new static();

        foreach($dbf->eloquent AS $model){
            if($force) $model->dropTable();
            $model->migrate();            
        }
    }

     public static function rebuild(){
        $dbf = new static();

        foreach($dbf->eloquent AS $model){
            $model->rebuildTable();
        }
    }

    public static function seed($force = false){
        $dbf = new static();

        foreach($dbf->eloquent AS $model){
            $model->seedTable($force);
        }
    }

     public static function update(){
        $dbf = new static();

        foreach($dbf->eloquent AS $model){
            $model->updateTable();
        }
    }

     public static function drop(){
        $dbf = new static();

        foreach($dbf->eloquent AS $model){
            $model->dropTable();
        }
    }
}