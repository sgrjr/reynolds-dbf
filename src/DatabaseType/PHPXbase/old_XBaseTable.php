<?php namespace App\Ask\DatabaseType\PHPXbase; 

/**
*
* This class provides the main entry to a DBF table file.
* common usage:
* 1. construct an instance
* 	$table = new XBaseTable($name);
* where $name is the path to the the DBF file, or a stream like 'php://input'
*
* 2. open the file to read the header
*	$table->open();
*
* 3. iterate through the records
*	while ($record=$table->nextRecord()) { ... }
*
* 4. close the file
*	$table->close();
*
**/

use App\Ask\DatabaseType\PHPXbase\XBaseColumn; 
use App\Ask\DatabaseType\PHPXbase\Memo; 

class OLDXBaseTable {

    var $name;
    var $fp;
    var $isStream;
    var $filePos=0;
    var $recordPos=-1;
    var $record;

    var $version;
    var $modifyDate;
    var $recordCount;
    var $recordByteLength;
    var $inTransaction;
    var $encrypted;
    var $mdxFlag;
    var $languageCode;
    var $columns;
    var $columnNames;
    var $headerLength;
    var $backlist;
    var $foxpro;
    var $deleteCount=0;

    public function __construct ($name, $skipMemo = true) {
        $this->name=$name;
        $this->skipMemo = true;

        $this->types = new \stdclass;
        $this->types->DBFFIELD_TYPE_MEMO = "M";      // Memo type field.
        $this->types->DBFFIELD_TYPE_CHAR = "C";      // Character field.
        $this->types->DBFFIELD_TYPE_NUMERIC = "N";   // Numeric
        $this->types->DBFFIELD_TYPE_FLOATING = "F";  // Floating point
        $this->types->DBFFIELD_TYPE_DATE = "D";      // Date
        $this->types->DBFFIELD_TYPE_LOGICAL = "L";   // Logical - ? Y y N n T t F f (? when not initialized).
        $this->types->DBFFIELD_TYPE_DATETIME = "T";  // DateTime
        $this->types->DBFFIELD_TYPE_INDEX = "I";    // Index 
        $this->types->DBFFIELD_IGNORE_0 = "0";       // ignore this field
        $this->writable = false;
        $this->fp = null;
        $this->init();
    }

    private function init(){
        return $this;
    }

    public function __destruct() {
        $this->close();
    }
    
    function newRecord($attributes = [], $deleted = false, $index = false) {
        //dd($this->recordCount);
        if($index === null || $index === false){

            $this->record = new XBaseRecord($this, $this->recordCount, $attributes, false, true);
            $this->recordCount+=1;
            $this->writeRecord();
            
            return $this->record;
        }else{
            //$table, $recordIndex, $rawData, $deleted
            return new XBaseRecord($this, $index, $attributes, $deleted);
        }
    }

    function open() {

        $this->isStream=strpos($this->name,"://")!==false;
        $fn = $this->name;
        if (!$this->isStream) {
            if (!file_exists($fn)) $fn = $this->name.".DBF";
            if (!file_exists($fn)) $fn = $this->name.".dbf";
            if (!file_exists($fn)) $fn = $this->name.".Dbf";
            if (!file_exists($fn)) trigger_error ($this->name." cannot be found", E_USER_ERROR);
        }

        $this->name = $fn;
        
        if($this->writable){
            $read_write_options = "r";
        }else{
            $read_write_options = "r+";
        }

        if($this->fp = fopen($fn,$read_write_options)) $this->readHeader()->setMemoTable();

        return $this; 
	}

	public function setMemoTable($skip = null){
        
        $memo = str_replace(".dbf", ".FPT", strtolower($this->name));
 
        if (!file_exists($memo)) $memo = str_replace(".dbf", ".fpt", $this->name);
        if (!file_exists($memo)){
            $this->memo = false;
        }else{
            $this->memo = new Memo($memo);
        }
        
        return $this;
    }

	function readHeader() {

        $this->version = $this->readChar();
        $this->foxpro = $this->version==48 || $this->version==49 || $this->version==245 || $this->version==251;
        $this->modifyDate = $this->read3ByteDate();
        $this->recordCount = $this->readInt();
        $this->headerLength = $this->readShort();
        $this->recordByteLength = $this->readShort();
        $this->readBytes(2); //reserved
        $this->inTransaction = $this->readByte()!=0;
        $this->encrypted = $this->readByte()!=0;
        $this->readBytes(4); //Free record thread
        $this->readBytes(8); //Reserved for multi-user dBASE
        $this->mdxFlag = $this->readByte();
        $this->languageCode = $this->readByte();
        $this->readBytes(2); //reserved

        $fieldCount = ($this->headerLength - ($this->foxpro?296:33) ) / 32;
        
        /* some checking */
        if (!$this->isStream && $this->headerLength>filesize($this->name)) trigger_error ($this->name." is not DBF", E_USER_ERROR);
        //if (!$this->isStream && $this->headerLength+($this->recordCount*$this->recordByteLength)-500>filesize($this->name)) trigger_error ($this->name." is not DBF", E_USER_ERROR);

        /* columns */
        $this->columnNames = array();
        $this->columns = array();
        $bytepos = 1;
        for ($i=0;$i<$fieldCount;$i++) {
            $column = new XBaseColumn(
                $this->readString(11),	// name
                $this->readByte(),		// type
                $this->readInt(),		// memAddress
                $this->readChar(),		// length
                $this->readChar(),		// decimalCount
                $this->readBytes(2),	// reserved1
                $this->readChar(),		// workAreaID
                $this->readBytes(2),	// reserved2
                $this->readByte()!=0,	// setFields
                $this->readBytes(7),	// reserved3
                $this->readByte()!=0,	// indexed
                $i,						// colIndex
                $bytepos,				// bytePos,
                $this
            );
            $bytepos+=$column->getLength();
            $this->columnNames[$i] = $column->getName();
            $this->columns[$i] = $column;
        }

        /**/
        if ($this->foxpro) {
            $this->backlist=$this->readBytes(263);
        }
        $b = $this->readByte();
        $this->recordPos=-1;
        $this->record=false;
        $this->deleteCount=0;

        return $this;
    }

    function isOpen() {
        return is_resource($this->fp);
    }

    function close() {
     if($this->isOpen()){
        fflush($this->fp);
        flock($this->fp,LOCK_UN);
        fclose($this->fp);
     }
     flush();
    }

    function nextRecord() {

        if ($this->recordPos+1 >= $this->recordCount) return false;
        $this->recordPos++;
        $this->record = new XBaseRecord($this,$this->recordPos,$this->readBytes($this->recordByteLength), false);

        if ($this->record->isDeleted()) {
            $this->deleteCount++;
        }

        return $this->record;
    }

    function recordsToArray($model){
        ini_set('memory_limit','512M');
        $startIndex = -1;
        $this->moveTo($startIndex);
        $list = [];

        while ($record1=$this->nextRecord() ) {
            $list[] = $record1->getData();
        }

        return $list;
    }

    
    function previousRecord() {

        $this->moveTo($this->recordPos-1);
        
        if ($this->recordPos < 0 || $this->recordPos > $this->recordCount) return false;
        
        $this->record = new XBaseRecord($this,$this->recordPos,$this->readBytes($this->recordByteLength));
        
        if ($this->record->isDeleted()) {
            $this->deleteCount++;
        }

        return $this->record;
    }

    function getRecord() {
        return $this->record;
    }
    function getColumnNames() {
        return $this->columnNames;
    }
    function getColumns() {
        return $this->columns;
    }
    function getColumn($index) {
        if(isset($this->columns[$index])){
            return $this->columns[$index];
        }else{
            return $this->getColumnByName($index);
        }
    }
    function getColumnByName($name) {
        foreach ($this->columnNames as $i=>$n) if (strtoupper($n) == strtoupper($name)) return $this->columns[$i];
        return false;
    }
    function getColumnIndex($name) {
        foreach ($this->columnNames as $i=>$n) if (strtoupper($n) == strtoupper($name)) return $i;
        return false;
    }
    function getColumnCount() {
        return sizeof($this->columns);
    }
    function getRecordCount() {
        return $this->recordCount;
    }

    function count() {
        return $this->recordCount;
    }

    function getRecordPos() {
        return $this->recordPos;
    }
    function getRecordByteLength() {
        return $this->recordByteLength;
    }
    function getName() {
        return $this->name;
    }
    function getDeleteCount() {
        return $this->deleteCount;
    }

    function getModifyDate(){
        return $this->modifyDate;
    }
     public function getHeader(){
            $header = new \stdclass;
            $header->name = $this->name;
            $header->fp = $this->fp;
            $header->name = $this->isStream;
            $header->filePos = $this->filePos;
            $header->recordPos = $this->recordPos;
            $header->record = $this->record;
            $header->version = $this->version;
            $header->modifyDate = $this->modifyDate;
            $header->recordCount = $this->recordCount;
            $header->recordByteLength = $this->recordByteLength;
            $header->inTransaction = $this->inTransaction;
            $header->encrypted = $this->encrypted;
            $header->mdxFlag = $this->mdxFlag;
            $header->languageCode = $this->languageCode;
            $header->columns = $this->columns;
            $header->columnNames = $this->columnNames;
            $header->headerLength = $this->headerLength;
            $header->backlist = $this->backlist;
            $header->foxpro = $this->foxpro;
            $header->deleteCount = $this->deleteCount;

        return $header;
     }

    /**
     * -------------------------------------------------------------------------
     * private functions
     * -------------------------------------------------------------------------
     */
    
    function seek($offset){
        fseek($this->fp,$offset);
    }
    
    function moveTo($index) {
        dd($index);
        $this->recordPos=$index;
        if ($index<0) return;
        $this->seek($this->headerLength+($index*$this->recordByteLength));
        $this->record = $this->newRecord($this->readBytes($this->recordByteLength),$this->recordPos, false);
        return $this->record;
    }

     function read($offset){
        return fread($this->fp,$offset);
     }

    function write($value, $offset = false){
        $this->log(json_encode($this->readHeader()));

        if($offset!= false) {
            $this->seek($offset);
        }

        $result = fwrite($this->fp,$value);
        //fflush($this->fp);

        $this->log("[result: ".$result."] header: ".json_encode($this->readHeader()));
        return $result;
    }

    function readBytes($l) {
        $this->filePos+=$l;
        return $this->read($l);
    }
    function writeBytes($buf) {
	    return $this->write($buf);
    }
    function readByte()  {
        $this->filePos++;
        return $this->read(1);
    }
    function writeByte($b)  {
        return $this->write($b);
    }
    function readString($l) {
        return $this->readBytes($l);
    }
    function writeString($s) {
        return $this->writeBytes($s);
    }
    function readChar() {
        $buf = unpack("C",$this->readBytes(1));
        return $buf[1];
    }
    function writeChar($c) {
	    $buf = pack("C",$c);
	    return $this->writeBytes($buf);
    }
    function readShort() {
        $buf = unpack("S",$this->readBytes(2));
        return $buf[1];
    }
    function writeShort($s) {
	    $buf = pack("S",$s);
	    return $this->writeBytes($buf);
    }
    function readInt() {
        $buf = unpack("I",$this->readBytes(4));
        return $buf[1];
    }
    function writeInt($i) {
	    $buf = pack("I",$i);
	    return $this->writeBytes($buf);
    }
    function readLong() {
        $buf = unpack("L",$this->readBytes(8));
        return $buf[1];
    }
    function writeLong($l) {
	    $buf = pack("L",$l);
	    return $this->writeBytes($buf);
    }
    function read3ByteDate() {
	    $y = unpack("c",$this->readByte());
	    $m = unpack("c",$this->readByte());
	    $d = unpack("c",$this->readByte());
        return mktime(0,0,0,$m[1],$d[1],$y[1]>70?1900+$y[1]:2000+$y[1]);
    }
    function write3ByteDate($d) {
	    $t = getdate($d);
	    return $this->writeChar($t["year"] % 1000) + $this->writeChar($t["mon"]) + $this->writeChar($t["mday"]);
    }
    function read4ByteDate() {
	    $y = readShort();
	    $m = unpack("c",$this->readByte());
	    $d = unpack("c",$this->readByte());
        return mktime(0,0,0,$m[1],$d[1],$y);
    }
    function write4ByteDate($d) {
	    $t = getdate($d);
	    return $this->writeShort($t["year"]) + $this->writeChar($t["mon"]) + $this->writeChar($t["mday"]);
    }

    function log($var, $additional_data = []){
        \App\Helpers\Misc::dbfLog(json_encode($var) . " " . $this->getName() . " " . json_encode($additional_data));
    }
}