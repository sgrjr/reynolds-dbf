<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Traits;

use Config;

trait DbfTableTrait {

	public function getDbfPrimaryKey(){
		return $this->dbfPrimaryKey;
	}

	protected $memo = "needs a Memo";
    protected $indexes = [];

    public function getMemo(){
        $config = Config::get("cp");
        $tablename = $this->getTable();
        return $config["tables"][$tablename]["memo"];
    }

    public function getUrlRootAttribute(){
        $config = Config::get("cp");
        $tablename = $this->getTable();
        return $config["tables"][$tablename]["urlroot"];
    }

    public function getTableExistsAttribute(){
        return \Schema::hasTable($this->getTable());
    }

    public function getIndexesAttribute(){
        return $this->indexes;
    }
		
}