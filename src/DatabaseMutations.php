<?php namespace App\Ask;

use DB, Config, Schema;
use App\Helpers\StringHelper;
use App\Dbf;
use App\Ask\Table;
use App\DBF\DbfQuery;

class DatabaseMutations {

	public function createTable($opt){
		$result = new \stdclass;
		
		if($opt->name === "ALL"){

			$tables = $this->config["tables"];

			foreach($tables AS $t){
				if($t["source"] !== "SEED" && Schema::hasTable($t["name"]) === false){	
					$headers = $this->getHeaders($t["name"]);
					$this->schema($t["name"], $headers);

					$dbf = Dbf::where("name", $t["name"])->first();
					$dbf->memo = "imported fresh";
					$dbf->save();

				}
				
			}
			$result->message= "All DBF Source Tables were created.";

		}else{

			//immediately return with error if table already exists.
			if(Schema::hasTable($opt->name)){
				$result->error = "Table " . $opt->name . " not created as it already exists in database.";
				return $result;
			}
			
			$table = $this->getTableByName($opt->name);

			if($table !== null & $table->source === "SEED"){
				$model = new $table->model;
				$model->createTable();
				$result->message = "Table " . $opt->name . " was created.";
			}else if($table !== null){	
				$headers = $this->getHeaders($table->name);
				$this->schema($table->name, $headers);
				$result->message = "Table " . $opt->name . " was created.";
			}

		}

		 return $result;
	}

	public function dropTable($opt){
		
		\DB::statement('SET FOREIGN_KEY_CHECKS=0;');

		if($opt->name === "ALL"){
		
			foreach($this->tables AS $t){
				if($t->source !== "SEED"){
					Schema::dropIfExists($t->name);
				}
				
			}
			
			Dbf::truncate();
			$message = "All DBF Source Tables deleted.";

		}else{

			if($opt->name !== "dbfs"){
				$dbf = Dbf::where("name",$opt->name)->first();
				$dbf->memo = "DELETED";
				$dbf->save();
			}

			Schema::dropIfExists($opt->name);
			$message = "Table " . $opt->name . " deleted.";
		}

		\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

		$result = new \stdclass;
		$result->message = $message;
		return $result;
	}
	
	public function truncateTable($opt){
		$dbf = Dbf::where("name",$opt->name)->first();
		$result = new \stdclass;

		if($opt->name !== "dbfs" && $dbf !== null){	
			$dbf->memo = "TRUNCATED";
			$dbf->save();
			$model = new $dbf->model;
			$model->truncate();			
			$result->message = "Table " . $opt->name . " truncated.";
		}else if($dbf === null){
			$result->error = "FAILED: dbfs Table not Truncated with: " . $opt->name . ". Dbf of entry with same name could not be found.";
		}else if($opt->name === "dbfs"){
			Dbf::truncate();
			$result->message = "Table " . $opt->name . " truncated.";
		}else{
			$result->message = "Nothing Happened.";
		}

		return $result;
	}
	
	
	
	public function seedTable($opt){

		ini_set('max_execution_time', 6000);
		ini_set('memory_limit', '1.5G');

		$result = new \stdclass;
		$result->message = null;
		
		if($opt->name === "ALL"){
		
			foreach($this->tables AS $t){
				if($t->source !== "SEED"){
					$result = $this->seedATable($t->name);

					if(isset($result->error) && $result->error !== null){
						$result->message .= $result->error;
					}else{
						$result->message .= $result->message;
					}
				}
				
			}

		}else{

			$result = $this->seedATable($opt->name);
		}

		return $result; 
	}
	
	public function importList($tablename, $rows){

		\Eloquent::unguard();

        //disable foreign key check for this connection before running seeders
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

		// insert rows
		$rows = array_chunk($rows, 500);
		
		\DB::transaction(function () use ($tablename, $rows){
			foreach($rows AS $r){
				\DB::table($tablename)->insert($r);
			}
		});	   

        // supposed to only apply to a single connection and reset it's self
        // but I like to explicitly undo what I've done for clarity
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
		
		return true;
		
	}

	public function schema($name, $headers){

		Schema::create($name,function($table) use ($name, $headers)
			{
				$table->increments('id');

				if(is_string($headers) || isset($headers->error)){
					dd($name,$headers);
				}

				$overRideToString = ["allsales","onhand","onorder"];

				foreach($headers AS $h){
					if(in_array(strtolower($h["name"]), $overRideToString)){
						$table->string(strtolower($h["name"]))->nullable();
					}else{


/*
B	-	d	Double
D	-	-	Date
F	N	d	Floating numeric field of width n with d decimal places
L	-	-	Logical
T	-	-	DateTime
Y	-	-	Currency
*/

					switch($h["type"]){
						case 'character':
						case 'C':
						case 'G':

							$table
								->string(strtolower($h["name"]), $h["length"])
								->charset('utf8')
								->collation('utf8_unicode_ci')
								->nullable();
							break;
						case 'number':
						case 'numeric':
						case 'Number':	
						case 'I':		//Integer	
						case 'N': //Numeric			
								$table->integer(strtolower($h["name"]), false)
								->nullable();		
							break;
						case 'memo':
						case 'M':
							$table
								->string(strtolower($h["name"]), $h["length"])
								->charset('utf8')
								->collation('utf8_unicode_ci')
								->nullable();
							break;
						default:
							$table
							->string(strtolower($h["name"]), 255)
							->charset('utf8')->
							collation('utf8_unicode_ci')
							->nullable();
					}
				}
			}
			$callback = $name."Schema";

			if(method_exists($this,$callback)){
				$table = call_user_func([$this, $callback], $table);	
			}

			$table->timestamps();
			$table->integer("deleted");
			});
		
	}

	private function seedATable($tableName){
		$result = new \stdclass;
		$result->message = null;

		if(!Schema::hasTable($tableName)){
			$result->error = "ERROR: Database is missing table named: " . $tableName;	
			return $result;
		}
		
		$table = $this->getTableByName($tableName);

		if(Dbf::count() < 1 ){
			$entries = $this->listTables();
			$this->importList("dbfs", $entries);
		}

		$dbf = Dbf::where("name",$tableName)->first();

		if($table->source === "SEED"){
			$model = new $table->model;
			
			switch($table->name){
				case 'exampleseededtablename':
					$entries = $this->listTables();
					break;
				default:
					$entries = [];
			}
		}else{

			$dm = new DatabaseManager();
			$callback = array($dm, 'importList');

        	$dbftable = Table::make($dbf, $callback); 

        	if(isset($dbftable->error)){
				$result->error = $dbftable->error;
				return $result;
			}else{
				$dbf->memo = "LOADED";
				$result->message = "Table: ".$table->name." was seeded from DBF.";
				$entries = $dbftable->rows;
			}
		}

			$this->importList($table->name, $entries);

			if($dbf !== null){
				$dbf->save();
			}

			return $result;
	}

}