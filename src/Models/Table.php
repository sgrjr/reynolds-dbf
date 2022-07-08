<?php namespace Sreynoldsjr\ReynoldsDbf\Models;

use Sreynoldsjr\ReynoldsDbf\Models\Column; 
use Sreynoldsjr\ReynoldsDbf\Models\Memo; 
use Sreynoldsjr\ReynoldsDbf\Models\Record; 
use Cache, Exception, stdclass;
use Sreynoldsjr\ReynoldsDbf\Helpers\Misc;
use Sreynoldsjr\ReynoldsDbf\Models\Traits\GlobalFieldTypesTrait;

/**
*
* This class provides the main entry to a DBF table file.
* common usage:
* 1. construct an instance
* 	$table = new Table($name);
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

class Table {

    use GlobalFieldTypesTrait;

    var $name;
    var $fp;
    var $isStream;
    var $filePos=0;
    var $recordPos=-1;
    var $record;
    var $raw = false;
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

    public function __construct ($name) {
        $this->name=$name;
        $this->fp = null;
        $this->read_write_options = "r+";
        $this->init();
    }

    private function init(){
        return $this;
    }

    public function setRaw(Bool $bool){
        $this->raw = $bool;
        return $this;
    }

    public function getRaw(){
        return $this->raw;
    }

    public function __destruct() {
        $this->close();
    }

    public function isAvailable(){
       if(file_exists($this->name)){
            try {
                $this->open();
                return $this->isOpen();
            }

            catch(Exception $e){
                return false;
            }
       }
       return false;
    }
    
    public function open() {

        $this->isStream=strpos($this->name,"://")!==false;
        if (!$this->isStream) {
            if (!file_exists($this->name)) {
                trigger_error ('file' . $this->name." cannot be found", E_USER_ERROR);
            }else{
                //dd($this->name);
            }
        }

        $this->initDbf();

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
                sleep(3);
                if($this->fp = fopen($this->name,$this->read_write_options)) {
                   $this->readHeader()->setMemoTable();
                }
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

        
        
        /* some checking */
        if (!$this->isStream && $this->headerLength>filesize($this->name)) trigger_error ($this->name." is not DBF", E_USER_ERROR);
        //if (!$this->isStream && $this->headerLength+($this->recordCount*$this->recordByteLength)-500>filesize($this->name)) trigger_error ($this->name." is not DBF", E_USER_ERROR);

        $this->cacheColumns();

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

    function isClosed() {
        return !is_resource($this->fp);
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
    
    function getMeta($toArray = false){
        $result = false;

        if($this->record === null || $this->record === false){
            if(!$this->isOpen()) $this->open(); 
            $result = $this->moveTo(1);
            if($this->isOpen()) $this->close(); 
        }else{
            $result = $this->record;
        }
        return $result->meta($toArray);
    }

    function getColumns($onlyOriginal=false) {
        if($this->columns === null){$this->open(); $this->close();}
        if($onlyOriginal){
            return array_filter($this->columns, function($v){
                return $v->original;
            });
        } 
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

        if($this->columns === null){$this->open(); $this->close();}

        foreach ($this->columnNames as $i=>$n) if (strtoupper($n) == strtoupper($name)) return $this->columns[$i];
        return false;
    }
    function getColumnIndex($name) {
        foreach ($this->columnNames as $i=>$n) if (strtoupper($n) == strtoupper($name)) return $i;
        return false;
    }
    function getColumnCount($onlyOriginal = false) {
        return sizeof($this->getColumns($onlyOriginal));
    }
    function getRecordCount() {
        if($this->recordCount === null){$this->open(); $this->close();}
        return $this->recordCount;
    }

    function count() {
        if($this->recordCount === null){$this->open(); $this->close();}
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
    
    function unique($columnName){
        $this->open();

        $results = [];

        $records = $this->getRecordCount();
        $col = $this->getColumnByName($columnName);

        for($i = 0; $i<$records; $i++){
            $this->seek($this->headerLength+($i*$this->recordByteLength)+$col->memAddress);
            $raw_data = trim($this->readBytes($col->length) ?? '');

            if($raw_data !== ""){
                $results[] = trim($raw_data ?? '');
            }
            
        }

        $this->close();

        return collect($results);
    }

    function cache(){
        $this->open();
        $cache = fopen($this->name . ".cache", "a");

        $records = $this->getRecordCount();

        for($i = 0; $i<$records; $i++){
            $this->seek($this->headerLength+($i*$this->recordByteLength));
            fwrite($cache, trim($this->readBytes($this->recordByteLength)) ?? '' . "\n");
        }

        $this->close();
        fclose($cache);

        return true;
    }

    function _rebuildFromCache(){
        //$file = file_get_contents($this->name . ".cache");
        dd('finsish creating function rebuildFromCache in Table line 389.');
        $file = new stdclass;

        $this->open();
        $this->seek(0);
        $file->header = new stdclass;
        $file->header->length = $this->headerLength;
        $file->header->raw = $this->readBytes($this->headerLength);
        

        dd($file->header->raw[1]);
        
        $this->close();
    }

    function moveTo($index) {

        $this->recordPos=$index;
        if ($index<0) return;
        $this->seek($this->headerLength+($index*$this->recordByteLength));
        $raw_data = $this->readBytes($this->recordByteLength);
        $inserted = false;

        $this->record = new Record($this, $this->recordPos, $raw_data);//$table, $recordIndex, $rawData, $deleted, $inserted = false

        return $this->record;
    }

     function read($offset){
        return fread($this->fp,$offset);
     }

    function write($value, $offset = false){

        if($offset!= false) {
            $this->seek($offset);
        }

        $result = fwrite($this->fp,$value); //file pointer INT

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
        Misc::dbfLog(json_encode($var) . " " . $this->getName() . " " . json_encode($additional_data));
    }

//Writing Functions
    // This update function is used to update or create
    // an entry in the dbf file
    // if "INDEX" is set it will update existing entry
    // otherwise it will append a new record

    function save($attributes) {
        $this->open();
        
       if(isset($attributes["INDEX"]) && $attributes["INDEX"] !== null && $attributes["INDEX"] !== ""){
        $attributes["INDEX"] = intval($attributes["INDEX"]);
        $this->moveTo($attributes["INDEX"]);
       }else{
        $this->appendRecord();
       }

        $this->record->copyFrom($attributes);
        $this->writeRecord();
        $this->close();

       return $this->record->getData();
    }

    function make($attributes) {
       return new Record($this, $this->recordCount,$attributes, false, true);
    }

    function restore($index) {
        $this->open();
        $this->moveTo($index);
        $this->record->restore();
        $this->writeRecord();
        $this->close();
       return $this->record->getData();
    }

     function delete($index) {
        $this->open();
        $this->moveTo($index);
        $this->record->delete();
        $this->writeRecord();
        $this->close();
       return $this->record->getData();
    }

    function appendRecord() {
        $this->record = new Record($this, $this->recordCount,[], false, true);
        $this->recordCount+=1;
        return $this->record;
    }

    function writeRecord() {

        $data = $this->record->serialize();

        if(strlen($data) !== $this->recordByteLength){
            $message = 'Cannot Save to file. Data for DBF is wrong Byte Length.' . strlen($data) . '-' . $this->recordByteLength;
            Misc::dbfLog($message);
            throw new \ErrorException(
                $message
            );
        }
        $offset = $this->record->recordIndex*$this->recordByteLength;
        $offset = $this->headerLength+($offset);
        $this->write($data, $offset);

        if ($this->record->inserted) {
            $this->writeHeader();
        }
    }

    function writeHeader() {

        $this->headerLength=($this->foxpro?296:33) + ($this->getColumnCount(true)*32);
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
        
        foreach ($this->getColumns(true) as $column) {
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

    public function getValueAttribute(){
        return file_get_contents($this->name);
    }

    public function setColumns($info){
        $this->columns = $info['columns'];
        $this->columnNames = $info['columnNames'];
        return $this;

    }
    public function cacheColumns(){
         /* columns */

        $name = strtolower(str_replace([DIRECTORY_SEPARATOR, '.',':'],"_",$this->name));
        $fieldCount = ($this->headerLength - ($this->foxpro?296:33) ) / 32;

        $this->setColumns($this->setTheColumns($fieldCount));

    }

    public function setTheColumns($fieldCount){
            $columns = [];
            $columnNames = [];
            $appendToEnd = [];

            $bytepos = 1;

            for ($i=0;$i<$fieldCount;$i++) {
                
                $column = new Column(
                    $this->readString(11),  // name
                    $this->readByte(),      // type
                    $this->readInt(),       // memAddress
                    $this->readChar(),      // length
                    $this->readChar(),      // decimalCount
                    $this->readBytes(2),    // reserved1
                    $this->readChar(),      // workAreaID
                    $this->readBytes(2),    // reserved2
                    $this->readByte()!=0,   // setFields
                    $this->readBytes(7),    // reserved3
                    $this->readByte()!=0,   // indexed
                    $i,                     // colIndex
                    $bytepos,               // bytePos,
                    $this->getName()
                );

                $bytepos+=$column->getLength();
                $columnNames[$i] = $column->getName();
                $columns[$i] = $column;

                if($column->getType() === $this->getGlobalFieldTypes()->DBFFIELD_TYPE_MEMO){
                    $memo_column_name = $column->getName() . "_MEMO";
                    $appendToEnd[ $memo_column_name] = new Column($memo_column_name, 'C' , false, 19, 0, false, false, false, false, false, false, count($columns), false, $this->getName(), false);
                }

            }

            foreach($appendToEnd AS $key=>$val){
                $columns[] = $val;
                $columnNames[] = $key;
            }

            /*if(in_array('UPASS',$columnNames)){
                $columns[] = new Column('password', 'C' , false, 255, 0, false, false, false, false, false, false, count($columns), false, $this->getName(), false);
                $columnNames[] = 'password';
            }*/

            $columns[] = new Column('deleted_at', 'C' , false, 19, 0, false, false, false, false, false, false, count($columns), false, $this->getName(), false);
            $columnNames[] = 'deleted_at';

            $columns[] = new Column('INDEX', 'C' , false, 15, 0, false, false, false, false, false, false, count($columns), false, $this->getName(), false);
            $columnNames[] = 'INDEX';
            return ['columns' => $columns, 'columnNames'=>$columnNames];
    }

    public function getTypesAttribute(){
        return $this->getGlobalFieldTypes();
    }

    public function __get($value){
        $method = 'get'. ucfirst(strtolower($value)) . 'Attribute';
        return $this->$method();
    }

}