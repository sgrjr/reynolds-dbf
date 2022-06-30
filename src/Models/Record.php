<?php namespace Sreynoldsjr\ReynoldsDbf\Models;
/**
*
* This class defines the data access functions to a DBF record
* Do not construct an instance yourself, generate records through the nextRecord function of XBaseTable
*
**/

class DataEntry{
    public function __construct($value, $type, $length, $original = true){
        $this->value = $value;
        $this->type = $type;
        $this->length = $length;
        $this->original = $original;
    }

    public static function make($value, $type, $length, $original = true){
        return new static($value, $type, $length, $original);
    }
}

class Record {

    var $zerodate = 0x253d8c;
    var $table;
    var $data;
    var $deleted_at;
    var $recordIndex;
    var $inserted;
    
 public function __construct($table, $recordIndex, $rawData, $deleted_at=null, $inserted = false) {

        $this->table =& $table;        
        $this->rawData = $rawData;
        $this->data = [];
        $this->inserted = $inserted;
        $this->recordIndex = $recordIndex;

        $filler = " "; //used to be: chr(0); 

        if(is_array($rawData)){
             foreach ($this->table->getColumns() as $column) {
                if(isset($rawData[$column->getName()])){
                    $this->data[$column->getName()]=str_pad($rawData[$column->getName()],$column->getDataLength(),$filler, STR_PAD_LEFT);
                }else{
                    $this->data[$column->getName()]=str_pad("", $column->getDataLength(),$filler,STR_PAD_LEFT);
                }
            }
        }else if ($rawData && strlen($rawData)>0) {
            $this->deleted_at=(ord($rawData)!="32")? now()->toDateTimeString():false;

            foreach ($table->getColumns() as $column) {
                $value = substr($rawData,$column->getBytePos(),$column->getDataLength());
                $this->transform($column, $value);
            }
        } else {
            $this->deleted_at = false;
            foreach ($table->getColumns() as $column) {
                $val=str_pad("", $column->getDataLength(),$filler, STR_PAD_LEFT);
                $this->data[$column->getName()] = DataEntry::make($val, $column->getType(), $column->getDataLength(), true);
            }
        }
        
        $this->initData();

    }

    function initData(){
        if(!isset($this->data["INDEX"])){
            $this->data["INDEX"] = DataEntry::make($this->getRecordIndex(), "Char", 15, false);
        }
        if(!isset($this->data["deleted_at"])){
            $this->data["deleted_at"] = DataEntry::make($this->deleted_at, "Char", 19, false);
        }
    }

    function transform($column, $value){
        if($column->getType() === "M"){
            $this->transformMemo($column,$value);
        }else if($column->name === "UPASS"){
            $this->transformPassword($column,$value);
        }else{
            $this->data[$column->getName()] = DataEntry::make($value, $column->getType(), $column->getDataLength(), true);
        }

    }

    function transformMemo($column, $value){
        $val = unpack("L", $value)[1];
        $val = $this->table->memo->getMemo($val)["text"];
        $this->data[$column->getName()] = DataEntry::make($value, 'BINARY', $column->getDataLength(), true);
        $this->data[$column->getName() . '_MEMO'] = DataEntry::make($val, 'TEXT', $column->getDataLength(), false);
    }

    function transformPassword($column, $value){
       $this->data[$column->getName()] = DataEntry::make($value, $column->getType(), $column->getDataLength());
    $this->data["password"] = DataEntry::make(\Hash::make($value), $column->getType(), $column->getDataLength(), false);           
    }

    function isDeleted() {
        if(!isset($this->deleted_at) || $this->deleted_at === null || $this->deleted_at === false) return false;
        return true;
    }

    function getColumns() {
        return $this->table->getColumns();
    }
    function getColumnByName($name) {
        return $this->table->getColumnByName($name);
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
        return $this->getString($this->table->getColumnByName($columnName));
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
        $index = $columnObj->getName();
        if (ord($this->data[$index])=="0") return false;
        return trim($this->data[$index]);
    }
    function getObjectByName($columnName) {
        return $this->getObject($this->table->getColumnByName($columnName));
    }
    function getObjectByIndex($columnIndex) {
        return $this->getObject($this->table->getColumn($columnIndex));
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
	    if ($columnObj->getType()!=$this->table->types->DBFFIELD_TYPE_DATE) trigger_error ($columnObj->getName()." is not a Date column", E_USER_ERROR);
        $s = $this->forceGetString($columnObj);
        if (!$s) return false;
        return strtotime($s);
    }
    function getDateTime($columnObj) {
        if ($columnObj->getType()!=$this->table->types->DBFFIELD_TYPE_DATETIME) trigger_error ($columnObj->getName()." is not a DateTime column", E_USER_ERROR);
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
        if ($columnObj->getType()!=$this->table->types->DBFFIELD_TYPE_LOGICAL) trigger_error ($columnObj->getName()." is not a DateTime column", E_USER_ERROR);
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
	    if ($columnObj->getType()!=$this->table->types->DBFFIELD_TYPE_MEMO) trigger_error ($columnObj->getName()." is not a Memo column", E_USER_ERROR);
        return $this->forceGetString($columnObj);
    }
    function getFloat($columnObj) {
	    if ($columnObj->getType()!=$this->table->types->DBFFIELD_TYPE_FLOATING) trigger_error ($columnObj->getName()." is not a Float column", E_USER_ERROR);
        $s = $this->forceGetString($columnObj);
        if (!$s) return false;
        $s = str_replace(",",".",$s);
        return floatval($s);
    }
    function getInt($columnObj) {

	    if ($columnObj->getType()!=$this->table->types->DBFFIELD_TYPE_NUMERIC) trigger_error ($columnObj->getName()." is not a Number column", E_USER_ERROR);
        $s = $this->forceGetString($columnObj);
        if (!$s) return false;
        $s = str_replace(",",".",$s);
        return intval($s);
    }
	function getIndex($columnObj) {
		if ($columnObj->getType()!=$this->table->types->DBFFIELD_TYPE_INDEX) trigger_error ($columnObj->getName()." is not an Index column", E_USER_ERROR);
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
        $ignore_columns = ["index","INDEX","deleted_at"];

        foreach ($record as $i=>$v) {

            if(in_array($i, $this->table->getColumnNames())){
                if (is_object($i))
                    $this->setString($i,$v);
                else if (is_numeric($i)) 
                    $this->setStringByIndex($i,$v);
                else 
                    $this->setStringByName($i,$v);
            }

        }
        if(isset($record["deleted_at"])){
            $this->setDeleted($record["deleted_at"]);
        }        
	}

    function setDeleted($b) {
       	$this->deleted_at=$b;
    }
    function setStringByName($columnName,$value) {
        $this->setString($this->table->getColumnByName($columnName),$value);
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
        $newValue = str_pad(substr($value,0,$columnObj->getDataLength()),$columnObj->getDataLength()," ");
        $this->data[$columnObj->getName()] = $newValue;
    }
    function setObjectByName($columnName,$value) {
        return $this->setObject($this->table->getColumnByName($columnName),$value);
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
	    if ($columnObj->getType()!=$this->table->types->DBFFIELD_TYPE_DATE) trigger_error ($columnObj->getName()." is not a Date column", E_USER_ERROR);
        if (strlen($value)==0) {
	        $this->forceSetString($columnObj,"");
	        return;
        }
       	$this->forceSetString($columnObj,date("Ymd",$value));
    }
    function setDateTime($columnObj,$value) {
        if ($columnObj->getType()!=$this->table->types->DBFFIELD_TYPE_DATETIME) trigger_error ($columnObj->getName()." is not a DateTime column", E_USER_ERROR);
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
        if ($columnObj->getType()!=$this->table->types->DBFFIELD_TYPE_LOGICAL) trigger_error ($columnObj->getName()." is not a DateTime column", E_USER_ERROR);
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
	    if ($columnObj->getType()!=$this->table->types->DBFFIELD_TYPE_MEMO) trigger_error ($columnObj->getName()." is not a Memo column", E_USER_ERROR);
        return $this->forceSetString($columnObj,$value);
    }
    function setFloat($columnObj,$value) {
	    if ($columnObj->getType()!=$this->table->types->DBFFIELD_TYPE_FLOATING) trigger_error ($columnObj->getName()." is not a Float column", E_USER_ERROR);
        if (strlen($value)==0) {
	        $this->forceSetString($columnObj,"");
	        return;
        }
        $value = str_replace(",",".",$value);
        $s = $this->forceSetString($columnObj,$value);
    }
    function setInt($columnObj,$value) {

	    if ($columnObj->getType()!=$this->table->types->DBFFIELD_TYPE_NUMERIC) trigger_error ($columnObj->getName()." is not a Number column", E_USER_ERROR);
        if (strlen($value)==0) {
	        $this->forceSetString($columnObj,"");
	        return;
        }
        $value = str_replace(",",".",$value);
        //$this->forceSetString($columnObj,intval($value));
        
        /**
        * suggestion from Sergiu Neamt: treat number values as decimals
        **/
        $this->forceSetString($columnObj,number_format($value, $columnObj->decimalCount,'.',''));
    }
    /**
     * -------------------------------------------------------------------------
 	 * Protected
     * -------------------------------------------------------------------------
     **/

     function serialize($delimit = false){

        $dataString = '';
        if($delimit) $dataString .= "'";
        $dataString .= $this->isDeleted()?"*":" ";
        if($delimit) $dataString .= "'";

        foreach($this->data AS $key=>$record){
            if($key !== "INDEX" && $key !== "deleted_at" && $record->original){
                $column = $this->table->getColumnByName($key);
                if($delimit) $dataString .= ",'";
                $dataString .= str_pad(trim($record->value), $column->getDataLength()," ",STR_PAD_LEFT); //use to be chr(0)
                if($delimit) $dataString .= "'";
            }
        }

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

    function getData($skipFields = [], $skipMemo = true) {
        $data = [];

        foreach($this->data AS $k=>$d){
            $data[$k] = $d->value;
        }
        return $data;
    }

    function meta(){
        return $this->data;
    }

     public function __get($prop){
        return $this->data[$prop]->value;
     }

}