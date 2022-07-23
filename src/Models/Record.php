<?php namespace Sreynoldsjr\ReynoldsDbf\Models;

use Sreynoldsjr\ReynoldsDbf\Helpers\DataEntry;
use Hash, stdclass;
/**
*
* This class defines the data access functions to a DBF record
* Do not construct an instance yourself, generate records through the nextRecord function of XBaseTable
*
**/

class Record {

    var $zerodate = 0x253d8c;
    var $table;
    var $data;
    var $deleted_at;
    var $recordIndex;
    var $inserted;
    
 public function __construct($table, $recordIndex, $rawData, $deleted_at=null, $inserted = false) {

        //$this->table =& $table;
        $this->table = new stdclass;
        $this->table->getRaw = $table->getRaw(); 
        $this->table->types = $table->types;
        $this->memo = $table->memo;
        $this->columns = $table->getColumns();
        $this->columnNames = $table->getColumnNames();

        $this->rawData = $rawData;
        $this->data = [];
        $this->inserted = $inserted;
        $this->recordIndex = $recordIndex;

        $filler = " "; //used to be: chr(0); 

        if(is_array($rawData)){

             $this->deleted_at = !isset($rawData["deleted_at"]) || $rawData["deleted_at"] == "0"? null:$rawData["deleted_at"];

             foreach ($this->getColumns() as $column) {
                if(isset($rawData[$column->name])){
                    $value = $rawData[$column->name]; //str_pad($rawData[$column->name],$column->getDataLength(),$filler, STR_PAD_LEFT);                
                }else{
                    $value = "";// str_pad("", $column->getDataLength(),$filler,STR_PAD_LEFT);
                }

                $this->data[$column->name] = DataEntry::make($value, $column);
            }
        }else if ($rawData && strlen($rawData)>0) {
            //if(str_contains( $rawData, '\86')) dd($rawData);
            $this->deleted_at=(ord($rawData)=="32")? null:now()->toDateTimeString();

            foreach ($table->getColumns() as $column) {

                if($column->original){
                    $value = substr($rawData,$column->getBytePos(),$column->getDataLength());
                }else{
                    $value = $this->getCustomField($column->name);
                }
                $this->transform($column, $value);
            }
        } else {
            $this->deleted_at = false;
            foreach ($table->getColumns() as $column) {
                $val= ""; //str_pad("", $column->getDataLength(),$filler, STR_PAD_LEFT);
                $this->data[$column->name] = DataEntry::make($val, $column);
            }
        }
        
        $this->initData();
        unset($val);
        unset($value);
        unset($filler);
        unset($inerted);
        unset($table);
        unset($recordIndex);
        unset($rawData);
        unset($deleted_at);

    }

    function getCustomField($field){

        switch($field){

            case 'INDEX':
                return $this->getRecordIndex();
            case 'deleted_at':
                return $this->deleted_at;
            case 'created_at':
            case 'updated_at':
                return null;
            case 'SYNOPSIS_MEMO':
                return $this->data['SYNOPSIS_MEMO']->value;
            default:
                if(str_contains($field, "_MEMO")){
                    $this->getMemoValue(str_replace("_MEMO","",$field));
                }else{
                    dd("custom field has no function" . $field);
                }
        }
    }

    function initData(){
        if(!isset($this->data["INDEX"])){
            $this->data["INDEX"] = DataEntry::make($this->getRecordIndex(), $this->getColumnByName("INDEX"));
        }
        if(!isset($this->data["deleted_at"])){
            $this->data["deleted_at"] = DataEntry::make($this->deleted_at, $this->getColumnByName("deleted_at"));
        }
        if(!isset($this->data['INDEX']->value) && is_string($this->data['INDEX']) && $this->data['INDEX'] === ""){
            $this->data['INDEX'] = $this->getRecordIndex();
        } else if(isset($this->data['INDEX']->value) && trim($this->data['INDEX']->value) === ""){
            $this->data['INDEX']->value = $this->getRecordIndex();
        }
    }

    function transform($column, $value){
        if($column->getType() === "M"){
            $this->transformMemo($column,$value);
        }else{
            $this->table->getRaw? 
                $this->data[$column->name] = trim($value ?? '') :
                $this->data[$column->name] = DataEntry::make(trim($value ?? ''), $column);
        }

    }

    function transformMemo($column, $value){
        $val = unpack("L", $value)[1];
        $val = $this->memo->getMemo($val)["text"];
        $this->data[$column->name] = DataEntry::make($value, $column);
        $this->data[$column->name . '_MEMO'] = DataEntry::make($val, $this->getColumnByName($column->name.'_MEMO'));
        return $this;
    }

    function _transformPassword($column, $value){
       $this->data[$column->name] = DataEntry::make($value, $column);
       $this->data["password"] = DataEntry::make(\Hash::make($value), $this->getColumnByName('password'));           
    }

    function isDeleted() {
            if(isset(
                $this->deleted_at) && 
                $this->deleted_at != null && 
                $this->deleted_at!= 0 && 
                $this->deleted_at != "0"
            ){
                return true;
            }else if(isset($this->deleted_at)){
                return false;
            }else if(is_string($this->rawData)){
                return ord($this->rawData)!="32";
            }

            return false;
    }

    function getColumns($onlyOriginal = false) {
         if($onlyOriginal){
            return array_filter($this->columns, function($v){
                return $v->original;
            });
        } 
        return $this->columns;
    }
    
    function getColumnByName($name) {
        foreach ($this->columns as $i=>$n) if (strtoupper($n->name) == strtoupper($name)) return $n;
        return false;
    }
    function getColumn($index) {
        return $this->table->getColumn($index);
    }
    function getColumnIndex($name) {
        return $this->table->getColumnIndex($name);
    }
    function getRecordIndex() {
        return $this->recordIndex;
    }

    /**
     * -------------------------------------------------------------------------
     * Get data functions
     * -------------------------------------------------------------------------
     */
    function getStringByName($columnName) {
        return $this->getString($this->getColumnByName($columnName));
    }
    function getStringByIndex($columnIndex) {
        return $this->getString($this->table->getColumn($columnIndex));
    }
    function getString($columnObj) {
        if ($columnObj->getType()==$this->table->types->DBFFIELD_TYPE_CHAR ) {
            return $this->forceGetString($columnObj);
        } else {
            $result = $this->getObject($columnObj);
            if ($result && ($columnObj->getType()==$this->table->types->DBFFIELD_TYPE_DATETIME || $columnObj->getType()==$this->table->types->DBFFIELD_TYPE_DATE)) return @date("r",$result);
            if ($columnObj->getType()==$this->table->types->DBFFIELD_TYPE_LOGICAL) return $result?"1":"0";
            return $result;
        }
    }
    function forceGetString($columnObj) {
        $index = $columnObj->name;
        if (ord($this->data[$index]->value)=="0") return false;
        return trim($this->data[$index]->value ?? '');
    }

    function getObjectByName($columnName) {
        return $this->getObject($this->getColumnByName($columnName));
    }
    function getObjectByIndex($columnIndex) {
        return $this->getObject($this->getColumn($columnIndex));
    }
    function getObject($columnObj) {

        if(!is_object($columnObj)){
            //not sure why but returning an error 
            //returning false here is a temp fix until I figure why an obj would ever 
            //have a value of false
            return false;
        }

        switch ($columnObj->getType()) {
            case $this->table->types->DBFFIELD_TYPE_CHAR : return $this->getString($columnObj);
            case $this->table->types->DBFFIELD_TYPE_DATE : return $this->getDate($columnObj);
            case $this->table->types->DBFFIELD_TYPE_DATETIME : return $this->getDateTime($columnObj);
            case $this->table->types->DBFFIELD_TYPE_FLOATING : return $this->getFloat($columnObj);
            case $this->table->types->DBFFIELD_TYPE_LOGICAL : return $this->getBoolean($columnObj);
            case $this->table->types->DBFFIELD_TYPE_MEMO : return $this->getMemo($columnObj);
            case $this->table->types->DBFFIELD_TYPE_NUMERIC : return $this->getInt($columnObj);
            case $this->table->types->DBFFIELD_TYPE_INDEX : return $this->getIndex($columnObj); 
            case $this->table->types->DBFFIELD_IGNORE_0 : return false;
        }
        trigger_error ("cannot handle datatype".$columnObj->getType(), E_USER_ERROR);
    }
    function getDate($columnObj) {
	    if ($columnObj->getType()!=$this->table->types->DBFFIELD_TYPE_DATE) trigger_error ($columnObj->name." is not a Date column", E_USER_ERROR);
        $s = $this->forceGetString($columnObj);
        if (!$s) return false;
        return strtotime($s);
    }
    function getDateTime($columnObj) {
        if ($columnObj->getType()!=$this->table->types->DBFFIELD_TYPE_DATETIME) trigger_error ($columnObj->name." is not a DateTime column", E_USER_ERROR);
        $raw =  $this->data[$columnObj->getColIndex()];
        $buf = unpack("i",substr($raw,0,4));
        $intdate = $buf[1];
        $buf = unpack("i",substr($raw,4,4));
        $inttime = $buf[1];

        if ($intdate==0 && $inttime==0) return false;

        $longdate = ($intdate-$this->zerodate)*86400;
        return $longdate+$inttime;
    }
    function getBoolean($columnObj) {
        if ($columnObj->getType()!=$this->table->types->DBFFIELD_TYPE_LOGICAL) trigger_error ($columnObj->name." is not a DateTime column", E_USER_ERROR);
        $s = $this->forceGetString($columnObj);
        if (!$s) return false;
        switch (strtoupper($s[0])) {
            case 'T':
            case 'Y':
            case 'J':
            case '1':
                return true;

            default: return false;
        }
    }
    function getMemo($columnObj) {
	    if ($columnObj->getType()!=$this->table->types->DBFFIELD_TYPE_MEMO) trigger_error ($columnObj->name." is not a Memo column", E_USER_ERROR);
        return $this->forceGetString($columnObj);
    }
    function getMemoValue($name){
        $obj = $this->getColumnByName($name);
        return $this->getMemo($obj);
    }

    function getFloat($columnObj) {
	    if ($columnObj->getType()!=$this->table->types->DBFFIELD_TYPE_FLOATING) trigger_error ($columnObj->name." is not a Float column", E_USER_ERROR);
        $s = $this->forceGetString($columnObj);
        if (!$s) return false;
        $s = str_replace(",",".",$s);
        return floatval($s);
    }
    function getInt($columnObj) {

	    if ($columnObj->getType()!=$this->table->types->DBFFIELD_TYPE_NUMERIC) trigger_error ($columnObj->name." is not a Number column", E_USER_ERROR);
        $s = $this->forceGetString($columnObj);
        if (!$s) return false;
        $s = str_replace(",",".",$s);
        return intval($s);
    }
	function getIndex($columnObj) {
		if ($columnObj->name!=$this->table->types->DBFFIELD_TYPE_INDEX) trigger_error ($columnObj->name." is not an Index column", E_USER_ERROR);
		$s = $this->data[$columnObj->getColIndex()];
		if (!$s) return false;
		
		$ret = ord($s[0]);
		for ($i = 1; $i < $columnObj->length; $i++) {
			$ret += $i * 256 * ord($s[$i]);
		}
		return $ret;   
	} 

    /**
     * -------------------------------------------------------------------------
 	 * Set data functions
     * -------------------------------------------------------------------------
     **/
	function copyFrom($record) {
        $ignore_columns = ["index","INDEX"];

        foreach ($record as $i=>$v) {

            /*if(isset($this->data[$i])){
                $this->data[$i]->value = $v->value;
            }*/

           if(in_array($i, $this->columnNames) && !in_array($i,$ignore_columns)){
                if (is_object($i))
                    $this->setString($i,$v);
                else if (is_numeric($i)) 
                    $this->setStringByIndex($i,$v);
                else 
                    if(is_object($v)){
                        $this->setStringByName($i,$v->value);
                    }else{
                        $this->setStringByName($i,$v);
                    }
            }

        }

        if(isset($record["deleted_at"])){
            $this->setDeleted($record["deleted_at"]);
        } 

	}

    function setDeleted($b) {
       	$this->deleted_at=$b;
        $this->transform($this->getColumnByName('deleted_at'), $b);
    }
    function setStringByName($columnName,$value) {
        $this->setString($this->getColumnByName($columnName),$value);
    }
    function setStringByIndex($columnIndex,$value) {
        $this->setString($this->table->getColumn($columnIndex),$value);
    }
    function setString($columnObj,$value) {
        if ($columnObj->getType()==$this->table->types->DBFFIELD_TYPE_CHAR) {
            $this->forceSetString($columnObj,$value);
        } else {
	        if ($columnObj->getType()==$this->table->types->DBFFIELD_TYPE_DATETIME || $columnObj->getType()==$this->table->types->DBFFIELD_TYPE_DATE) $value = strtotime($value);
            $this->setObject($columnObj,$value);
        }
    }

    function forceSetString($columnObj,$value) {
        if(is_object($value)) {$value = $value->value;}
        $newValue = str_pad(substr($value,0,$columnObj->getDataLength()),$columnObj->getDataLength()," ");
        $this->data[$columnObj->name] = $newValue;
    }

    function setObjectByName($columnName,$value) {
        return $this->setObject($this->getColumnByName($columnName),$value);
    }
    function setObjectByIndex($columnIndex,$value) {
        return $this->setObject($this->table->getColumn($columnIndex),$value);
    }
    function setObject($columnObj,$value) {
        switch ($columnObj->getType()) {
            case $this->table->types->DBFFIELD_TYPE_CHAR : $this->setString($columnObj,$value); return;
            case $this->table->types->DBFFIELD_TYPE_DATE : $this->setDate($columnObj,$value); return;
            case $this->table->types->DBFFIELD_TYPE_DATETIME : $this->setDateTime($columnObj,$value); return;
            case $this->table->types->DBFFIELD_TYPE_FLOATING : $this->setFloat($columnObj,$value); return;
            case $this->table->types->DBFFIELD_TYPE_LOGICAL : $this->setBoolean($columnObj,$value); return;
            case $this->table->types->DBFFIELD_TYPE_MEMO : $this->setMemo($columnObj,$value); return;
            case $this->table->types->DBFFIELD_TYPE_NUMERIC : $this->setInt($columnObj,$value); return;
            case $this->table->types->DBFFIELD_IGNORE_0 : return;
        }
        trigger_error ("cannot handle datatype".$columnObj->getType(), E_USER_ERROR);
    }
    function setDate($columnObj,$value) {
	    if ($columnObj->name!=$this->table->types->DBFFIELD_TYPE_DATE) trigger_error ($columnObj->name." is not a Date column", E_USER_ERROR);
        if (strlen($value)==0) {
	        $this->forceSetString($columnObj,"");
	        return;
        }
       	$this->forceSetString($columnObj,date("Ymd",$value));
    }
    function setDateTime($columnObj,$value) {
        if ($columnObj->getType()!=$this->table->types->DBFFIELD_TYPE_DATETIME) trigger_error ($columnObj->name." is not a DateTime column", E_USER_ERROR);
        if (strlen($value)==0) {
	        $this->forceSetString($columnObj,"");
	        return;
        }
        $a = getdate($value);
        $d = $this->zerodate + (mktime(0,0,0,$a["mon"],$a["mday"],$a["year"]) / 86400);
        $d = pack("i",$d);
        $t = pack("i",mktime($a["hours"],$a["minutes"],$a["seconds"],0,0,0));
        $this->data[$columnObj->getColIndex()] = $d.$t;
    }
    function setBoolean($columnObj,$value) {
        if ($columnObj->getType()!=$this->table->types->DBFFIELD_TYPE_LOGICAL) trigger_error ($columnObj->name." is not a DateTime column", E_USER_ERROR);

        if(is_object($value)) $value = $value->value;

        switch (strtoupper($value)) {
            case 'T':
            case 'Y':
            case 'J':
            case '1':
            case 'F':
            case 'N':
            case '0':
                $this->forceSetString($columnObj,$value);
                return;
            
            case true:
                $this->forceSetString($columnObj,"T");
                return;

            default: $this->forceSetString($columnObj,"F");
        }
    }
    function setMemo($columnObj,$value) {
	    if ($columnObj->getType()!=$this->table->types->DBFFIELD_TYPE_MEMO) trigger_error ($columnObj->name." is not a Memo column", E_USER_ERROR);
        return $this->forceSetString($columnObj,$value);
    }
    function setFloat($columnObj,$value) {
	    if ($columnObj->getType()!=$this->table->types->DBFFIELD_TYPE_FLOATING) trigger_error ($columnObj->name." is not a Float column", E_USER_ERROR);
        if (strlen($value)==0) {
	        $this->forceSetString($columnObj,"");
	        return;
        }
        $value = str_replace(",",".",$value);
        $s = $this->forceSetString($columnObj,$value);
    }
    function setInt($columnObj,$value) {

	    if ($columnObj->getType()!=$this->table->types->DBFFIELD_TYPE_NUMERIC) trigger_error ($columnObj->name." is not a Number column", E_USER_ERROR);

        if(is_object($value)) $value = $value->value;

        if (strlen($value)==0) {
	        $this->forceSetString($columnObj,"");
	        return;
        }
        $value = str_replace(",",".",$value);
        //$this->forceSetString($columnObj,intval($value));
        
        /**
        * treat number values as decimals
        **/
        $this->forceSetString($columnObj,number_format((Int) $value, $columnObj->decimalCount,'.',''));
    }
    /**
     * -------------------------------------------------------------------------
 	 * Protected
     * -------------------------------------------------------------------------
     **/

     function serialize($delimit = false){
        $columns = [];
        $dataString = '';
        if($delimit) $dataString .= "'";
        $dataString .= $this->isDeleted()?"*":" ";

        if($delimit) $dataString .= "'";

        foreach($this->getColumns(true) AS $column){
            $columnName = $column->name;
            $columns[] = $columnName;
                if($delimit) $dataString .= ",'";
                $val = $this->data[$columnName];

                if(is_object($val)){
                    $val = trim($val->value ?? '');
                    $length = $this->data[$columnName]->length;
                }else{
                    $val = trim($val ?? '');
                    $length = $column->getLength();
                }

                $dataString .= str_pad($val, $length," ", STR_PAD_RIGHT);
                if($delimit) $dataString .= "'";

        }
        //dd(new static($this->table, $this->recordIndex, $dataString));
        //var_dump(count($this->getColumns(true)));
        //dd($dataString);
        //var_dump($this->table->recordByteLength);
        //dd($columns);
        //dd($this->table->recordCount);
        return $dataString;
     }

    function delete(){
        $this->setDeleted(now()->toDateTimeString());
        return $this;
     }

    function restore(){
        $this->setDeleted(false);
        return $this;
     }
    
    function unDelete(){
       return $this->restore();
     }

     function json(){
        return json_encode($this->getData());
     }

     function toJson(){
        return $this->json();
     }

     function toArray(){
        return $this->getData();
     }

      function attributes(){
        return $this->getData();
     }

    function getData($skipFields = [], $skipMemo = true) {
        $data = [];

        foreach($this->data AS $k=>$d){
            if(is_object($d)){
                $data[$k] = $d->value === ''? null:$d->value;
            }else{
                $data[$k] = $d === ''? null:$d;
            }
            
        }
        return $data;
    }

    function meta($toArray = false){

        if($toArray){
            $x = [];

            foreach($this->data AS $data){
                $x[$data->name] = $data->toArray();
            }

            return $x;
        }else{
            return $this->data;
        }
        
    }

     public function __get($prop){
        return $this->data[$prop]->value;
     }

}