<?php namespace Sreynoldsjr\ReynoldsDbf;

use Sreynoldsjr\ReynoldsDbf\Models\Model;
use stdclass;

class ReynoldsDbf
{

    public function __construct(){
        $this->files = [];
        $this->initFiles();
    }

    public function initFiles(){

         $config = config('reynolds-dbf');
        
        if($config['find_dbfs']){

        }else{
            foreach($config['files'] AS $key=>$file){
                $this->files[$key] = new Model($config['root_paths'][$file[1]] . DIRECTORY_SEPARATOR . $file[0]);
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

        $q = json_decode('{"root":"webhead", "filters":[{"f":"KEY","o":"=","v":5}], "page":1, "perPage":5, "props":["KEY",{"root":"webdetail", "filters":[{"f":"ISCOMPLETE","o":"=","v":false}]}]}');
        
        $root = (new static)->model($q->root);
        
        $limit = $q->page * $q->perPage;

        $data = [];

        $root->table->open();

        //open the file to read the header
        $root->table->open();

        //iterate through the records
        while ($record=$root->table->nextRecord()) { 
            $data[]=$record->toArray();
            if(count($data) >= $limit){break;}
        }

        //close the file
        $root->table->close();

        return $data;
    }

}