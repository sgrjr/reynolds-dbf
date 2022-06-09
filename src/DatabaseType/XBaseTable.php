<?php namespace App\Ask\DatabaseType; 

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

use App\Ask\DatabaseType\XBaseColumn; 
use App\Ask\DatabaseType\Memo; 

class XBaseTable {

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

    public function __construct ($name, $skipMemo = true, $writable = false) {
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
        $this->writable = $writable;
        $this->fp = null;
        $this->init();
    }

    private function init(){
        return $this;
    }

    public function __destruct() {
        $this->close();
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
            $this->read_write_options = "r+";
        }else{
            $this->read_write_options = "r";
        }

        //try {
            $this->initDbf();
        //}
        //catch(\Exception $e){
            //\App\Jobs\UpdateDbfWhenReady::dispatchSync($this);
         //   echo $e;
        //}
        return $this; 
	}

    public function initDbf(){
           try {
                if($this->fp = fopen($this->name,$this->read_write_options)) {
                    $this->readHeader()->setMemoTable();
                }
            }

            catch(\Exception $exception){
                echo 'Could not open. File is already open.';
                //maybe someone is saving changes and we just need to wait to try to open again.
               // sleep(3);
                //if($this->fp = fopen($this->name,$this->read_write_options)) {
                   // $this->readHeader()->setMemoTable();
                //}
                return false;
            }
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
        
        $this->record = $this->moveTo($this->recordPos);
        
        if ($this->record->isDeleted()) {
            $this->deleteCount++;
        }
        return $this->record;
    }

    function recordsToArray(){
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

        $this->record = $this->moveTo($this->recordPos-1);
        
        if ($this->recordPos < 0 || $this->recordPos > $this->recordCount) return false;
        
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

        $this->recordPos=$index;
        if ($index<0) return;
        $this->seek($this->headerLength+($index*$this->recordByteLength));
        $raw_data = $this->readBytes($this->recordByteLength);
        $inserted = false;

        $this->record = new XBaseRecord($this, $this->recordPos, $raw_data);//$table, $recordIndex, $rawData, $deleted, $inserted = false

        return $this->record;
    }

     function read($offset){
        return fread($this->fp,$offset);
     }

    function write($value, $offset = false){
        
        if($offset!= false) {
            $this->seek($offset);
        }
   
        $result = fwrite($this->fp,$value);
        fflush($this->fp);

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

//Writing Functions
    // This update function is used to update or create
    // an entry in the dbf file
    // if "INDEX" is set it will update existing entry
    // otherwise it will append a new record

    function save($attributes) {
       if(isset($attributes["INDEX"]) && isset($attributes["INDEX"]) !== null){
        $attributes["INDEX"] = intval($attributes["INDEX"]);
        $this->moveTo($attributes["INDEX"]);
       }else{
        $this->appendRecord();
       }

        $this->record->copyFrom($attributes);
        $this->writeRecord();

       return $this->record->getData();
    }

    function appendRecord() {
        $this->record = new XBaseRecord($this, $this->recordCount,[], false, true);
        $this->recordCount+=1;
        return $this->record;
    }

    function writeRecord() {
        $data = $this->record->serialize();

        if(strlen($data) !== $this->recordByteLength){
            $message = 'Cannot Save to file. Data for DBF is wrong Byte Length.' . strlen($data) . '-' . $this->recordByteLength;
            \App\Helpers\Misc::dbfLog($message);
            throw new \ErrorException(
                $message
            );
        }

        $offset = $this->headerLength+($this->record->recordIndex*$this->recordByteLength);

        $this->write($data, $offset);

        if ($this->record->inserted) {
            $this->writeHeader();
        }
    }

    function writeHeader() {

        $this->headerLength=($this->foxpro?296:33) + ($this->getColumnCount()*32);
        $this->seek(0);
        $this->writeChar($this->version);
        $this->write3ByteDate(time());
        $this->writeInt($this->recordCount);
        $this->writeShort($this->headerLength);
        $this->writeShort($this->recordByteLength);
        $this->writeBytes(str_pad("", 2,chr(0)));
        $this->writeByte(chr($this->inTransaction?1:0));
        $this->writeByte(chr($this->encrypted?1:0));
        $this->writeBytes(str_pad("", 4,chr(0)));
        $this->writeBytes(str_pad("", 8,chr(0)));
        $this->writeByte($this->mdxFlag);
        $this->writeByte($this->languageCode);
        $this->writeBytes(str_pad("", 2,chr(0)));
        
        foreach ($this->columns as $column) {
            $this->writeString(str_pad(substr($column->rawname,0,11), 11,chr(0)));
            $this->writeByte($column->type);
            $this->writeInt($column->memAddress);
            $this->writeChar($column->getDataLength());
            $this->writeChar($column->decimalCount);
            $this->writeBytes(str_pad("", 2,chr(0)));
            $this->writeChar($column->workAreaID);
            $this->writeBytes(str_pad("", 2,chr(0)));
            $this->writeByte(chr($column->setFields?1:0));
            $this->writeBytes(str_pad("", 7,chr(0)));
            $this->writeByte(chr($column->indexed?1:0));
        }

        if ($this->foxpro) {
            $this->writeBytes(str_pad($this->backlist, 263," "));
        }
        $this->writeChar(0x0d);
    }

    /* DISPLAYING DATA */

      function toHTML($start_index = -1, $withHeader=true,$tableArgs="border='1'",$trArgs="",$tdArgs="",$thArgs="") {
        $result = "<table $tableArgs >\n";
        if ($withHeader) {
            $result .= "<tr>\n";
            foreach ($this->getColumns() as $i=>$c) {
                $result .= "<th $thArgs >".$c->getName()."</th>\n";
            }
            $result .= "</tr>\n";
        }
        $this->moveTo($start_index);
        while ($r = $this->nextRecord()) {
            $result .= "<tr $trArgs >\n";
            foreach ($this->getColumns() as $i=>$c) {
                $result .= "<td $tdArgs >".$r->getString($c)."</td>\n";
            }
            $result .= "</tr>\n";
        }
        $result .= "</table>\n";
        return $result;
    }

    function toXML() {
        $result = "<table ";
        $result.= "name='".$this->name."' ";
        $result.= "version='".$this->version."' ";
        $result.= "modifyDate='".$this->modifyDate."' ";
        $result.= "recordCount='".$this->recordCount."' ";
        $result.= "recordByteLength='".$this->recordByteLength."' ";
        $result.= "inTransaction='".$this->inTransaction."' ";
        $result.= "encrypted='".$this->encrypted."' ";
        $result.= "mdxFlag='".ord($this->mdxFlag)."' ";
        $result.= "languageCode='".ord($this->languageCode)."' ";
        $result.= "backlist='".base64_encode($this->backlist)."' ";
        $result.= "foxpro='".$this->foxpro."' ";
        $result.= "deleteCount='".$this->deleteCount."' ";
        $result.= ">\n";
        $result .= "<columns>\n";
        foreach ($this->getColumns() as $i=>$c) {
            $result .= "<column ";
            $result .= "name='".$c->name."' ";
            $result .= "type='".$c->type."' ";
            $result .= "length='".$c->length."' ";
            $result .= "decimalCount='".$c->decimalCount."' ";
            $result .= "bytePos='".$c->bytePos."' ";
            $result .= "colIndex='".$c->colIndex."' ";
            $result .= "/>\n";
        }
        $result .= "</columns>\n";
        $result .= "<records>\n";
        $this->moveTo(-1);
        while ($r = $this->nextRecord()) {
            $result .= "<record>\n";
            foreach ($this->getColumns() as $i=>$c) {
                $result .= "<".$c->name.">".$r->getObject($c)."</".$c->name.">\n";
            }
            $result .= "</record>\n";
        }
        $result .= "</records>\n";
        $result .= "</table>\n";
        return $result;
    }

}