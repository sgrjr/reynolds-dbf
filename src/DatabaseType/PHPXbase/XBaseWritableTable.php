<?php namespace App\Ask\DatabaseType\PHPXbase; 
/**
* ----------------------------------------------------------------
*			XBase
*			XBaseWritableTable.class.php	
* 
* --------------------------------------------------------------
*
* This class extends the main entry to a DBF table file, with writing abilities

*
**/
use App\Ask\DatabaseType\PHPXbase\XBaseColumn; 

class XBaseWritableTable extends XBaseTable {
	private function init(){
		$this->writable = true;
	}

	public function __destruct() {
        $this->close();
    }

	/* static */
	function cloneFrom($table) {
		$result = new XBaseWritableTable($table->name);
	    $result->version=$table->version;
	    $result->modifyDate=$table->modifyDate;
	    $result->recordCount=0;
	    $result->recordByteLength=$table->recordByteLength;
	    $result->inTransaction=$table->inTransaction;
	    $result->encrypted=$table->encrypted;
	    $result->mdxFlag=$table->mdxFlag;
	    $result->languageCode=$table->languageCode;
	    $result->columns=$table->columns;
	    $result->columnNames=$table->columnNames;
	    $result->headerLength=$table->headerLength;
	    $result->backlist=$table->backlist;
	    $result->foxpro=$table->foxpro;
	    return $result;
	}

	/* static */
	function create($filename,$fields) {
		if (!$fields || !is_array($fields)) trigger_error ("cannot create xbase with no fields", E_USER_ERROR);
		$recordByteLength=1;
		$columns=array();
		$columnNames=array();
		$i=0;
		foreach ($fields as $field) {
			if (!$field || !is_array($field) || sizeof($field)<2) trigger_error ("fields argument error, must be array of arrays", E_USER_ERROR);
			$column = new XBaseColumn($field[0],$field[1],0,@$field[2],@$field[3],0,0,0,0,0,0,$i,$recordByteLength, $this);
			$recordByteLength += $column->getDataLength();
			$columnNames[$i]=$field[0];
			$columns[$i]=$column;
			$i++;
		}
		
		$result = new XBaseWritableTable($filename);
	    $result->version=131;
	    $result->modifyDate=time();
	    $result->recordCount=0;
	    $result->recordByteLength=$recordByteLength;
	    $result->inTransaction=0;
	    $result->encrypted=false;
	    $result->mdxFlag=chr(0);
	    $result->languageCode=chr(0);
	    $result->columns=$columns;
	    $result->columnNames=$columnNames;
	    $result->backlist="";
	    $result->foxpro=false;
	    if ($result->openWrite($filename,true)) return $result;
	    return false;
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

	function save($att, $ignore_columns = []){

		if(isset($att["INDEX"]) && $att["INDEX"] !== null && $att["INDEX"] !== false && $att["INDEX"] <= $this->count()+1){
			$att["INDEX"] = intVal($att["INDEX"]);
			$this->moveTo($att["INDEX"]);
			$offset = $this->headerLength+($att["INDEX"]*$this->recordByteLength);
		}else{
			$this->record = $this->newRecord($att, false, false);
			$offset = $this->headerLength+($this->recordCount*$this->recordByteLength);
			$att["INDEX"] = $this->recordCount;
			$this->recordCount+=1;

		}
		
		$this->log(json_encode($att), ["index"=>$this->record->recordIndex]);

		$this->write($this->record->serialize(), $offset);

		$this->moveTo($att["INDEX"]);		

		foreach($this->record->getData() AS $k=>$v){
			if(!in_array($k, $ignore_columns)){
				$att[$k] = $v;
			}
		}
		return $att;
	}

	function delete($model){
		$this->moveTo($model->INDEX);
		$this->deleteRecord();
		return true;
	}

	function unDelete($model){
		$this->moveTo($model->INDEX);
		$this->undeleteRecord();
		return true;
	}
/*
	/// If the model already exists in the database we can just delete our record
        // that is already in this database using the current IDs in this "where"
        // clause to only update this model. Otherwise, we'll just insert them.
        $table->moveTo((int) $this->INDEX);
        $record = $table->getRecord();
        if ($record !== null) {
            $record->setDeleted(true);
            $table->writeRecord();
        }
        */

	function update($record){
		$this->record = $record;
		$this->writeRecord();
	}

	function readAll(){
		
		$this->seek($this->headerLength);
		//Output lines until EOF is reached
		echo "<ol>";

		while(($buffer = fgets($this->fp, $this->recordByteLength)) !== false) {
		  echo "<li>".$buffer. "</li>";
		}
		if (!feof($this->fp)) {
        	echo "Error: unexpected fgets() fail\n";
    	}
		echo "</ol>";
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

		if ($this->record->inserted) $this->writeHeader();
	}
	function deleteRecord() {
		$this->record->deleted=true;
		$offset = $this->headerLength+($this->record->recordIndex*$this->recordByteLength);
		$this->write("*", $offset);
	}
	function undeleteRecord() {
		$this->record->deleted=false;
		$offset = $this->headerLength+($this->record->recordIndex*$this->recordByteLength);
		$this->write(" ", $offset);
	}

	function pack() {
		$newRecordCount = 0;
		$newFilepos = $this->headerLength;
		for ($i=0;$i<$this->getRecordCount();$i++) {
			$r = $this->moveTo($i);
			if ($r->isDeleted()) continue;
			$r->recordIndex = $newRecordCount++;
			$this->writeRecord();
		}
		$this->recordCount = $newRecordCount;
		$this->writeHeader();
		ftruncate($this->fp,$this->headerLength+($this->recordCount*$this->recordByteLength));
	}

}