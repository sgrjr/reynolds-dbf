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
	    $result = \Schema::dropIfExists( $this->getTable() );

	    if(!Schema::hasTable($this->getTable())){
	    	$this->w("<fg=green>[".$this->getTable()."] Table dropped successfully.");
	    }else{
	    	$this->w("<fg=red>[".$this->getTable()."] Table not dropped.");
	    }

		\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
		$this->migrationDelete();
		return $this;
	}

	public function w($message, $line = true){
		$output = new ConsoleOutput();
		$line? $output->writeln($message):$output->write($message);
	}

     public static function progressBar($output, $steps = 1, $message = 'starting'){       
          $progressBar = new ProgressBar($output, $steps);
          $progressBar->setBarCharacter('<fg=green>⚬</>');
          $progressBar->setEmptyBarCharacter("<fg=red>⚬</>");
          $progressBar->setProgressCharacter("<fg=green>➤</>");
          $progressBar->setFormat("<fg=white;bg=cyan> %status:-45s%</>\n%current%/%max% [%bar%] %percent:3s%% %estimated:-20s% %memory:20s%", $progressBar->getFormatDefinition('debug')); // the new format
          $progressBar->setMessage($message, 'status');
          $progressBar->start();

          return $progressBar;

          //$progressBar->advance();
          //$progressBar->finish();
     }

	public function migrationDelete(){
		$migration_name = str_replace(".php",'', $this->migration);
		$deleted = DB::delete('delete from migrations where migration = "'.$migration_name.'"');

		$exists_still = DB::table('migrations')->where('migration',$migration_name)->exists();

		if($exists_still){
			$this->w("<fg=red>Migration still exists [".$this->getTable()."].");
		}else{
			$this->w("<fg=green>Migration removal run for [".$this->getTable()."].");
		}

		
		return $this;
	}

	public function rebuildTable(){
		$output = new ConsoleOutput();
		$this->w("<fg=green>Rebuilding table [".$this->getTable()."] beginning.");
		$this->dropTable();
		$this->migrate();
		$this->seedTable();
		$this->w("<fg=green>Rebuilding table [".$this->getTable()."] completed.");
		return $this;
	}

    public function emptyTable(){
    	$output = new ConsoleOutput();
    	if( \Schema::hasTable($this->getTable() ) ){
    		\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
	    	$this->truncate();
	    	$this->w("<fg=green>Emptied table [".$this->getTable()."] successful.");
			\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    	}else{
    		$this->w("<fg=red>Emptying table [".$this->getTable()."] failed.");
    	}
    	return $this;
    }

    public function migrate(){
    	$result = \Artisan::call('migrate --path=/vendor/sreynoldsjr/reynolds-dbf/database/migrations/' . $this->migration);
    	////php artisan migrate --path=/database/migrations/full_migration_file_name_migration.php
    	return $this;
    }

    public function createTable(){

        if(Schema::hasTable($this->getTable())){
            $this->dropTable();
        }

		Schema::create($this->getTable(),function($table) {
			$table->increments('id');
            $table = Misc::setUpTableFromHeaders($table, $this->headers, $this);
            $table->charset = 'utf8mb4';
			$table->collation = 'utf8mb4_unicode_ci';				
		});

		$this->w("<fg=green>[".$this->getTable()."] Table created successful.");		

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
		$this->w("<fg=green>[".$this->getTable()."] Foreign keys added successful.");
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
		$this->w("<fg=green>[".$this->getTable()."] Foreign keys dropped successful.");
		return $this;
    }

    public function getForeignKeys(){
        return ($this->foreignKeys)? $this->foreignKeys:[];
    }

	public function seedTable($force = false){

		if($force === false && DB::table($this->getTable())->count() > 0){
			$this->w("<fg=red>[".$this->getTable()."] Table not seed because it was already seeded.");
			return false;	
		} 

    	$this
    		->emptyTable()
    		->seedFromConfig()
    		->seedFromDBF()
    		->doAfterSeed();
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
                $rd = $record->getData();
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

	public function seedFromConfig(){
		$conf = Config::get('reynolds-dbf');

		if(!isset($conf['seeds']) || !isset($conf['seeds'][$this->getTable()])){
			$this->w("<fg=red>[".$this->getTable()."] No seeds in config.");
			return $this;
		}

		$this->w("<fg=red>[".$this->getTable()."] Beginning to seed from Config: ");

		foreach($conf['seeds'][$this->getTable()] AS $k=>$v){
			if($c === $this->getTable()){
				foreach($v as $props){
					$this->create($props);
					$this->w(".", false);
				}
			}
			
		}

		return $this;
	}	

	public function seedFromDBF(){
            ini_set('memory_limit','512M');
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::connection()->unsetEventDispatcher();

            $output = new ConsoleOutput();
            $iteration_limit = 500;
            $file = $this->dbf()->database();
            $file->open();
            //$file->moveTo(849221);
            
            	$bag = [];
            	$count = $file->count();
            	$output->write("<fg=green>STARTING TO IMPORT from DBF: " . $count . " RECORDS...");

            	$progressBar = new ProgressBar($output, $count);
				$progressBar->setBarCharacter('<fg=green>⚬</>');
				$progressBar->setEmptyBarCharacter("<fg=red>⚬</>");
				$progressBar->setProgressCharacter("<fg=green>➤</>");
				$progressBar->setFormat("<fg=white;bg=cyan> %status:-45s%</>\n%current%/%max% [%bar%] %percent:3s%% %estimated:-20s% %memory:20s%", $progressBar->getFormatDefinition('debug')); // the new format

            	$progressBar->start();

	            while ($record=$file->nextRecord() ) {
	                $bag[] = $record->getData();
	                $progressBar->setMessage($this->getTable() . " [" . $record->INDEX ."]", 'status'); // set the `status` value
	                $progressBar->advance();

	                if(count($bag) === $iteration_limit){
	                    $this->insert($bag);
	                    unset($bag);
	                    $bag = [];
	                }
	            }

	            $this->insert($bag);
	            $bag = [];
	            $progressBar->finish();

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
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

	public static function logger($message){
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
