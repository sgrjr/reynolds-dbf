<?php namespace App\Ask\DatabaseType; 
/**
* This class represents a DBF column
* Do not construct an instance yourself, it's useless that way.
**/
class XBaseColumn extends \ArrayObject {

    var $name;
    var $rawname;
    var $type;
    var $memAddress;
    var $length;
    var $decimalCount;
    var $workAreaID;
    var $setFields;
    var $indexed;
    var $bytePos;
    var $colIndex;
    private $container = array();

    public function __construct(
        $name,
        $type,
        $memAddress,
        $length,
        $decimalCount,
        $reserved1,
        $workAreaID,
        $reserved2,
        $setFields,
        $reserved3,
        $indexed,
        $colIndex,
        $bytePos,
        $table
    ) {
        $this->rawname=$name;
        $this->name=preg_replace('/[^a-zA-Z0-9-_\.]/','', strpos($name,"0x00")!==false?substr($name,0,strpos($name,"0x00")):$name);
        $this->type=$type;
        $this->memAddress=$memAddress;
        $this->length=$length;
        $this->decimalCount=$decimalCount;
        $this->workAreaID=$workAreaID;
        $this->setFields=$setFields;
        $this->indexed=$indexed;
        $this->bytePos=$bytePos;
        $this->colIndex=$colIndex;

        $this->container = [
            "name"=>$this->getName(),
            "length"=>$this->getLength(),
            "type"=>$this->getType(),
            "decimal_count" => $this->getDecimalCount(),
            "mysql_type"=>$this->getMySQLType(),
            "nullable" => true
        ];

        $this->table = $table;
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->container[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->container[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    function getDecimalCount() {
        return $this->decimalCount;
    }
    function isIndexed() {
        return $this->indexed;
    }
    function getLength() {
        return $this->length;
    }
    function getDataLength() {
	    switch ($this->type) {
            case $this->table->types->DBFFIELD_TYPE_DATE : return 8;
            case $this->table->types->DBFFIELD_TYPE_DATETIME : return 8;
            case $this->table->types->DBFFIELD_TYPE_LOGICAL : return 1;
            case $this->table->types->DBFFIELD_TYPE_MEMO : return 10;
            default : return $this->length;
	    }
    }
    function getMemAddress() {
        return $this->memAddress;
    }
    function getName() {
        return $this->name;
    }
    function isSetFields() {
        return $this->setFields;
    }
    function getType() {
        return $this->type;
    }
    function getWorkAreaID() {
        return $this->workAreaID;
    }
    function toString() {
        return $this->name;
    }
    function getBytePos() {
        return $this->bytePos;
    }
    function getRawname() {
        return $this->rawname;
    }
    function getColIndex() {
        return $this->colIndex;
    }
    function getContainer(){
      
      return $this->container;

        $exactLengths = [
            "KEY" => 14,
            "PROD_NO" => 14,
            "ISBN" => 14,
            "INDEX" => 12,
            "DELETED" => 1, 
            "FDATE" => 12,
            "REMOTEADDR" => 20,
            "TRANSNO" => 20,
            "LASTTIME" => 20,
            "LASTDATE" => 12,
            "ORDREASON" => 20,
            "ORDACTION" => 20,
            "JOBBERHOLD" => 5,
            "SENDSTATUS" => 10,
            "SERIES" => 12,
            "TIMESTAMP" => 12,
            "INVNATURE" => 5,
            "DATESTAMP" => 8,
            "MASTERDATE" => 8,
            "ONODATE" => 10,
            "ORDERDATE" => 10,
            "DATE" => 8,
            "REMDATE" => 8,
            "ENTRYDATE" => 8,
            "ORDERED" => 8,
            "STATUS" => 20,
            "TITLE" => 75,
            "SUBTITLE" => 128,
            "TITLEKEY" => 75,
            "AUTHORKEY" => 60,
            "UPASS" => 128,
            "USERPASS" => 128,
            "FORMAT" => 15,
            "CAT" => 32,
            "SOPLAN" => 35,
            "COMPUTER" => 35,
            "ORDERNUM" => 20,
            "CXNOTE" => 255,
            "CINOTE" => 255,
            "TINOTE" => 255,
            "F997SENT" => 20,"F855SENT" => 20,"F810SENT" => 20,"F856SENT" => 20,
            "POSTCODE" => 10,
            "ORGNAME" => 128,
            "COMPANY" => 128,
            "VISION" => 32,
            "SEX" => 10,
            "ARTICLE" => 10,
            "CARTICLE" => 10,
            "SECONDARY" => 128,
            "REMOVED" => 70,
            "EMCHANGE" => 12,
            "ZIP5" => 10,
            "COUNTRY" => 20,
            "VOICEPHONE" => 16,
            "FAXPHONE" => 16,
            "RECALLD" => 8,
            "FIRST" => 32,
            "MIDNAME" => 32,
            "LAST" => 32,
            "AFIRST" => 32,
            "ALAST" => 32,
            "AUTHPRE" => 32,
            "SUFFIX" => 32,
            "AUTHORPRE2" => 32,
            "AFIRST2" => 32,
            "ALAST2" => 32,
            "SUFFIX2" => 32,
            "HIGHLIGHT" => 128,
            "EWHERE" => 10,
            "UPSELL" => 20,
            "FSTATUS" => 7

        ];

     	$overRideToString = ["ALLSALES","ONHAND","ONORDER","TRANSNO"];
		$overRideToNumber = ["PUBDATE"];
        $minimize = [

            //from PASSWORDS
            "LOGINS", "PRINTQUE","MULTIBUY","FULLVIEW","SKIPBOUGHT","OUTOFPRINT","OPROCESS","OBEST","OADDTL","OVIEW","ORHIST","OINVO","EXTZN","INSOS","INREG","LINVO","NOEMAILS","PROMOTION","PROMOTIONS","STATE","COMMCODE","MDEPARTMENT","PASSCHANGE","EXTENSION","PIC","ADVERTISE",

            //from inventories
            "HOLDNOW","PUBSTATUS","FCAT","SCAT","SGROUP",

            //from details
            "FASTPRINT","FSTATUS","TESTTRAN","HOLDNOW", "KILLNOW", "BACKNOW", "SHIPNOW", "TONHAND", "SOMSHIP", "RENSHIP", "MOMSHIP", "COMSHIP","ELSEWHERE","ARTICLE","CATALOG",

            //vendors
            "NATURE","WHAT"
        ];

        $h = $this->container;
        

            $name = $h["name"];

                if(in_array($name, $overRideToString)){
    			    $h["type"] = "String";
    		    }else if(in_array($name, $overRideToNumber)){
    			    $h["type"] = "Int";
    		    }else if(in_array($name, $overRideToTinyNumber)){
                    $h["type"] = "TinyInt";
                    $h["length"] = 1;
                }else{
    				$h["type"] = $types[$h['type']];
    		    }
                


                if($h["type"] === "Char"){

                    $h["length"] = 61;

                    if(in_array($name, $minimize)){
                        $h["length"] = 5;
                    }
                }
            
            if(isset($exactLengths[$name])){
                $h["length"] = $exactLengths[$name];
            }
           // echo "NAME: " . $h["name"] . " TYPE: " . $h["type"] . " LENGTH: " . $h['length'] . PHP_EOL;
            
            return $h;
	}

    private function getMySQLType(){
       
        $types = [
            "B" => "Double",
            'C' => "Char", //C  N   -   Character field of width n
            "Y" => "Decimal",//Y    -   -   Currency
            "F" => "Float", //F N   d   Floating numeric field of width n with d decimal places,
            "D" => "Date", //D  -   -   Date
            "G" => "Blob", //G  -   -   General
            "I" => "Integer", //I   -   -   Index
            "L" => "TinyInt", //L   -   -   Logical - ? Y y N n T t F f (? when not initialized).
            "M" => "Text", //M  -   -   Memo
            "N" => "Decimal", //N   N   d   Numeric field of width n with d decimal places
            "T" => "Datetime", //T  -   -   DateTime,
            "0" => "IGNORE" //// ignore this field
        ];

       return $types[$this->getType()];
    }
}