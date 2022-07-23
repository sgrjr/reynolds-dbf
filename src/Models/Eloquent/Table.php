<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent;

use Sreynoldsjr\ReynoldsDbf\Models\Table AS DbfTable; 
use Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Column;
use Illuminate\Support\Facades\Storage;
use stdclass;

class Table {

    public function __construct ($model) {

        $this->attributes = [
            'name' => $model->database,
            'table' => $model->getTable(),
            'model' => $model
        ];

        $this->cache_name = "dbfs/".$this->table . "_dbf_table.cache";

        $this->init();
    }
////     dd($this->dbf()->database()->getColumnByName($col));
    public function __get($att){

        if(isset($this->attributes[$att])){
            return $this->attributes[$att];
        }

        $method = 'get'. ucfirst(strtolower($att)) . 'Attribute';
        return $this->$method();
    }

    private function init(){
        if(Storage::has($this->cache_name)){
            $this->data = json_decode(Storage::get($this->cache_name));
            $this->data->columns = (Array) $this->data->columns;
        }else{
            $this->data = $this->initializeFromDbf();
        }
    }

    public function getColumnByName($name){
        return new Column((Array) $this->data->columns[$name]);
    }

    private function initializeFromDbf(){
        $dbf = $this->model->dbf();
        $data = new stdclass;
        $data->columns = [];

        foreach($dbf->database()->getColumns() AS $column){
            $data->columns[$column->name] = $column->toArray();
            $data->columns[$column->name]['getDataLength'] = $column->getDataLength();
            $data->columns[$column->name]['getDecimalCount'] = $column->decimalCount;
            $data->columns[$column->name]['getMySQLType'] = $column->getMySQLType();
        }
        
        Storage::put($this->cache_name, json_encode($data));
        return $data;
    }

     public function getMeta($toArray = false){
       return $this->attributes['model']->dbf()->meta($toArray);
    }

}

/*function getColumnNames() {
     return $this->columnNames;
    }
    */