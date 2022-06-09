<?php namespace App\Ask;

use App\Ask\AskInterface\AskQueryBuilderInterface;
use App\Ask\QueryBuilder;

class ConfigTable {
    public function __construct($model){
        $this->model = $model;
        $this->name = $model->getTable();
        $this->loadData();
        $this->index = -1;
    }

    public function loadData(){
       $rootpath = \Config::get('cp')["datarootpath"];
       $file_name = $rootpath . "/" . $this->model->getSeeds();

       if(!file_exists($file_name)){
            $response = \App\Helpers\TerminalCommands::exec("EXPORT_DBF_TO_XML",["table"=>$this->model->getTable()]);
            sleep(15);
            if(!file_exists($file_name)){
                sleep(15);
                if(!file_exists($file_name)){
                    sleep(15);
                }
            }

       }

        $file = file_get_contents($file_name);

        $items = [];

        $xml = simplexml_load_string($file);

        foreach($xml->booktext as $item)
        {
           $props = [];

           foreach($item as $key => $value)
           {
                $props[strtoupper((string)$key)] = (string)$value;
           }

           $items[] = $props;
        }

        $this->data = $items;

        return $this;
    }

    public function getRecords(){
        return $this->data;
    }

    public function getRecordCount(){
        return count($this->data);
    }

    public function setIndex($index){
        $this->index = $index;
    }

    public function nextRecord(){
        $this->index++;
        return $this->getRecord();
    }

    public function getRecord(){
        return isset($this->data[$this->index])? $this->data[$this->index]: false;
    }

    public function getObjectByName($name){

    }

    public function getRecordPos(){
        return $this->index;
    }

    public function close(){
        return $this;
    }
}

class XmlQueryBuilder extends QueryBuilder implements AskQueryBuilderInterface {

    public function setTable(){
        $this->table = new ConfigTable($this->model);
        return $this;
    }

    public function with($arrayOfRelationshipNames){
        return $this;
    }
    
    public function where($column, $comparison, $value){
        $this->parameters->tests[] = [$column, $comparison, $value];
        return $this;
    }
    
    public function setPage($pageValue){
        $this->parameters->page = $pageValue;
        return $this;
    }

    public function setIndex($index){
        $this->table->setIndex($index);
        return $this;
    }
    
    public function findByIndex($index, $columns = false){
        return $this;
    }
    
    public function find($primaryKeyValue){

        return $this
            ->setPerPage(1)
            ->setPage(1)
            ->setIndex(-1)
            ->where($this->model->getDbfPrimaryKey(),"===", $primaryKeyValue)
            ->get()->records->first();
    }

    public function index($index, $columns = false){
        return $this;
    }

    public function get($columns = false){

        $this->autoSetColumns($columns);
        $headers = $this->model->getFillable();
        $current_date_time = \Carbon\Carbon::now()->toDateTimeString();

        $counter = 0;
        
        if(count($this->columns) > 0){

        while ($recordx=$this->table->nextRecord()) {

            if($tests = $this->test($recordx)){
            $record = [];
            foreach($recordx AS $key => $val){
                $record[$key] = $val;
            }

            $record["INDEX"] = $this->table->getRecordPos();
                
                if(isset($record['deleted']) === false){
                    $record['deleted'] = false;
                }

                $this->addDataRecord($record);
                $counter++;

                if($counter === $this->parameters->perPage){break;}
            }

        }        
    }

        $lastIndex = $this->table->getRecordPos();
        $total = $this->table->getRecordCount();

        $this->updatePaginator($total, $lastIndex);

        $this->table->close();
        
        return $this->data;
    }
     
    public function first($columns = false){
        $this->autoSetColumns($columns);
        return $this->model->make((Array) $this->table->first($this->columns));
    }

    public function count(){
        return count($this->data->records);
    }

}
