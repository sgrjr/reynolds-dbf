<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits;

use Config, Cache, DB, Schema;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Helper\ProgressBar;
use Sreynoldsjr\ReynoldsDbf\Helpers\Misc;
use Sreynoldsjr\ReynoldsDbf\Helpers\Compare;

Trait ManageTableTrait
{

	 public function dropTable(){
		\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
	    \Schema::dropIfExists( $this->getTable() );
		\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

		$this->migrationDelete();

		return $this;
	}

	public function migrationDelete(){
		$deleted = DB::delete('delete from mirgrations where migration = "'.str_replace(".php",'',$this->migration).'"');
		return $this;
	}

    public function emptyTable(){
    	if( \Schema::hasTable($this->getTable() ) ){
    		\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
	    	static::truncate();
			\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    	}
    	
	    return $this;
    }

    public static function migrate(){
    	$that = new static;
    	\Artisan::call('migrate --path=/vendor/sreynoldsjr/reynolds-dbf/database/migrations/' . $that->migration);
    	////php artisan migrate --path=/database/migrations/full_migration_file_name_migration.php
    }

    public function createTable(){

        if(Schema::hasTable($this->getTable())){
            $this->dropTable();
        }

		Schema::create($this->getTable(),function($table) {
			$table->increments('id');
            $table = Misc::setUpTableFromHeaders($table, $this->headers, $this);
            $table->charset = 'utf8';
			$table->collation = 'utf8_unicode_ci';			
		});		

		return $this;
	}

	public function addForeignKeys(){
		$keys = $this->getForeignKeys();
		Schema::disableForeignKeyConstraints();
		Schema::table($this->getTable(),function($table) use($keys) {
			foreach($keys AS $fk){
            	$table->foreign($fk[0])->references($fk[1])->on($fk[2]);
        	}
		});	
		Schema::enableForeignKeyConstraints();
		return $this;
	}

    public function dropForeignKeys(){
       $keys = $this->getForeignKeys();

		Schema::table($this->getTable(),function($table) use($keys) {
			foreach($keys AS $fk){
				$k = $fk[0];
            	$table->dropForeign([$k]);
        	}
		});	
		return $this;
    }

    public function getForeignKeys(){
        return ($this->foreignKeys)? $this->foreignKeys:[];
    }

	public function seedTable(){
		
    	$this->emptyTable();

        foreach($this->getSeeds() AS $seed){ // array[type,id,path]

			switch($seed['type']){
				
				case 'xml':
					foreach($this->xml()->all()->records AS $item){
						$item->save();
					}
					break;

				case 'config':
					$conf = Config::get('cp');

					foreach($conf[$seed['id']] AS $mdl){
						$model = static::create($mdl);
					}
					break;

				case 'dbf':
					$this->seedFromDBF();
					$this->doAfterSeed();
					break;
				default:
					break;
			}

		}

		return $this;

	}

	public function entries($params){
            ini_set('memory_limit','512M');
            \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            $file = $this->dbf()->database();
            $file->open();
            $bag = collect([]);

            if($params->testsComparison == "COUNT"){
            	$bag = 0;
            }

            while ($record=$file->nextRecord() ) {
                $rd = $record->getData($this->getIgnoreColumns());
                if(Compare::test($record->getData(), $params)){
                	
                	if($params->testsComparison == "COUNT"){
		            	$bag++;
		            }else{
		            	$bag->push($rd);
		            }
              	}
            }

            \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $file->close();

            return $bag;

	}	

	public function seedFromDBF(){
            ini_set('memory_limit','512M');
            $output = new ConsoleOutput();
            \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            $file = $this->dbf()->database();
            $file->open();
            
            	$bag = [];
            	$count = $file->count();
            	$output->write("<fg=green>STARTING TO IMPORT: " . $count . " RECORDS...");

            	$progressBar = new ProgressBar($output, $count);
				$progressBar->setBarCharacter('<fg=green>⚬</>');
				$progressBar->setEmptyBarCharacter("<fg=red>⚬</>");
				$progressBar->setProgressCharacter("<fg=green>➤</>");
				$progressBar->setFormat("<fg=white;bg=cyan> %status:-45s%</>\n%current%/%max% [%bar%] %percent:3s%% %estimated:-20s% %memory:20s%", $progressBar->getFormatDefinition('debug')); // the new format

            	$progressBar->start();

	            while ($record=$file->nextRecord() ) {
	                $rd = $record->getData($this->getIgnoreColumns());
	                $bag[] = $rd;
	                $progressBar->setMessage($this->getTable() . " [" . $rd["INDEX"] ."]", 'status'); // set the `status` value
	                $progressBar->advance();
	                if(count($bag) > 400){
	                    $this->insert($bag);
	                    $bag = [];
	                }
	            }

	            $this->insert($bag);
	            $bag = [];
	            $progressBar->finish();

            \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $file->close();

            unset($dbf);
            unset($bag);
            unset($file);
            unset($rd);

            return $this;

	}	

	public function doAfterSeed(){
		//
	}

	public function logger($message){
		$logger = new ConsoleOutput();
		$logger->writeLn($message);

		//open the file
		$file = fopen(storage_path() . '\logs\dbf_report.log', "a+");
		//write 
		fwrite($file, $message .PHP_EOL);
		//close the file
		fclose($file);
	}

	public function report(){

		$update_these = collect([]);
		$skip = ["INDEX","deleted_at","id","created_at","updated_at","deleted_at"];

		$count = $this->count();
		
		$this->logger("<fg=green>STARTING REPORT for ".$this->getTable().": ...");

		$count = $this->withTrashed()->count();
		$dbfCount = $this->dbf()->count();

		$type = "<fg=green>";

		if($count !== $dbfCount) {
			$type = "<fg=red>";
		}

    	$this->logger($type . "- There are [".$count."] MYSQL and [".$dbfCount."] DBF.");

    	$records = static::whereNull("INDEX")->orWhere("INDEX","")->get();

    	if($records->count() > 0){
    		foreach($records AS $rec){
				$rec->update($rec->dbf()->save()->toArray());
			}
    	}

    	$type = "<fg=green>";

    	$this->dbf()->loop(function($record) use ($type, $update_these, $skip){

    		$mysql_model = static::findByIndex($record->INDEX, true); //make sure to include trashed

    		$dbfSerialized = $record->serialize(true);

    		if(!$mysql_model) {
    			$update_these->push($record->INDEX);
    			$model = (new static($record->getData()))->save();
    		}else{
    			$mysqlSerialized = $mysql_model->serialize(true);

	    		$areTheySame = strcmp($mysqlSerialized,$dbfSerialized);

	    		if($areTheySame <> 0){

	    			$diff = $this->get_difference($dbfSerialized, $mysqlSerialized);

    				$this->logger("<fg=red> Difference: " . $diff);
    				$this->logger("<fg=red> DBF: " . $dbfSerialized);
    				$this->logger("<fg=red> MYS: " . $mysqlSerialized);

	    			foreach($this->getHeadersAttribute() AS $key=>$column){
	    				if(!in_array($key,$skip)){
	    					$container = $record->table->getColumnByName($key);
		    				if($container === false) {
		    					$this->logger("<fg=red> Column does not exist in dbf: " . $key);
		    				}else{
		    					$container = $container->getContainer();

		    					if(implode(",",$column) == implode(",",$container)){
		    						//$this->log("<fg=green>". $key . " column definitions match.");
		    					}else{
		    						$this->logger("<fg=red> MYS: ". implode(",",$container));
		    						$this->logger("<fg=red> DBF: ". implode(",",$column));
		    					}
		    				}
		    			}	    				
	    			}

	    			//$this->logger($type . "- MYSQL: " . $mysqlSerialized);
	    			//$this->logger($type . "- DBF: " . $dbfSerialized);
	    			$update_these->push($record->INDEX);
	    			$mysql_model->update($record->getData());
	    		}
	    		$lengths = strlen($mysqlSerialized) . "-" . strlen($dbfSerialized);

	    		$this->logger($type . "- " . $areTheySame? "same" . $lengths:"not same" . $lengths);
    		}
    	}, -1, $dbfCount);
    	$this->logger($type . "- UPDATED: " . $update_these->implode(','));
	}

	public function get_difference($first, $second){
	    $diff = strlen($first) == strlen($second)?"":strlen($first)."/".strlen($second);

	    for($i=0; $i < strlen($first); $i++){
	    	if($first[$i] == $second[$i]){
	    		$diff .= '.';
	    	}else{
	    		$diff .= "[".$i."|".$first[$i] . "!=" . $second[$i]."]";
	    	}
	    }

	    return $diff;
	}

}
