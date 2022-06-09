<?php namespace App\Ask\DatabaseType\Config;

use App\Ask\DatabaseType\Config\ConfigColumn;
use App\Ask\DatabaseType\Config\ConfigCRecord;

class ConfigTable {

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

    public function __construct ($name) {
        $this->name=$name;
    }
    
    function open() {
    	$this->fp = include($this->name);
		//$this->readHeader();
		return $this;
	}
	
	function readHeader() {

       $this->version = null; //$this->readChar();
       $this->foxpro = false;
       $this->getStats();

       return $this;
    }
    function isOpen() {
        return $this->fp?true:false;
    }
    function close() {
        //fclose($this->fp);
        return $this;
    }

    function nextRecord() {}
    function moveTo($index) {}

    function getRecord() {return $this->record;}
    function getColumnNames() {
        return $this->columnNames;
    }
    function getColumns() {
        return $this->columns;
    }
    function getColumn($index) {
        return $this->columns[$index];
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
    
    function toHTML($withHeader=true,$tableArgs="border='1'",$trArgs="",$tdArgs="",$thArgs="") {
	    $result = "<table $tableArgs >\n";
	    if ($withHeader) {
		    $result .= "<tr>\n";
		    foreach ($this->getColumns() as $i=>$c) {
			    $result .= "<th $thArgs >".$c->getName()."</th>\n";
		    }
		    $result .= "</tr>\n";
	    }
	    $this->moveTo(-1);
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
    
    /**
     * -------------------------------------------------------------------------
     * private functions
     * -------------------------------------------------------------------------
     */
    function getStats() {
        //$this->fp = fopen($fn,"rb");
        $this->stats = fstat ( $this->fp );
        return $this;
    }




    ///////////////////////////////////////////////////////////
    function readBytes($l) {
        $this->filePos+=$l;
        return fread($this->fp,$l);
    }
    function writeBytes($buf) {
	    return fwrite($this->fp,$buf);
    }
    function readByte()  {
        $this->filePos++;
        return fread($this->fp,1);
    }
    function writeByte($b)  {
        return fwrite($this->fp,$b);
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
}