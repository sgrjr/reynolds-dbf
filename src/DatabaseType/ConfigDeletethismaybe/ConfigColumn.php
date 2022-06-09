<?php namespace App\Ask\DatabaseType\Config;
/**
* This class represents a DBF column
* Do not construct an instance yourself, it's useless that way.
**/
class ConfigColumn extends \ArrayObject {

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
        $bytePos
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
            "type"=>$this->getType()
        ];
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
            case DBFFIELD_TYPE_DATE : return 8;
            case DBFFIELD_TYPE_DATETIME : return 8;
            case DBFFIELD_TYPE_LOGICAL : return 1;
            case DBFFIELD_TYPE_MEMO : return 10;
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
}