<?php namespace App\Ask;

use DB, Config, Schema;
use App\Helpers\StringHelper;
use App\Dbf;
use App\DBF\DbfQuery;
use App\Ask\DatabaseMutations;

class Database {

	public function __construct (Array $query, $changes = false)
    {
       
        $this->changes = $changes;
        $this->mysql = false;
        //$query is an array of Table Classes//
    	$this
    		->setConfig()
    		->setViewer()
    		//->setMysql()
    		->makeChanges()
    		->setTables($query);
            
    }

	//This function is what is called to ask the Database a Question
    // $data = Database::get($query);
    //$query is an array of Table Objects
    
    public static function get(Array $query){
        return new self($query);
    }

	private function setTables($query){
        
        $this->tables = [];

		foreach($query AS $q){

            $table = $q;

             if(is_object($table)){

                $table
                    ->setRoot($this->viewer)
                    ->setSource($table->source)
                    ->setData();

            }else{
                dd($table, "ERROR: TABLE MUST BE AN OBJECT!");
            }

            $this->tables[$table->listName] = $table;
        }

		return $this;
	}

	private function setConfig(){
		 $this->config = Config::get("cp");
		 return $this;
	}

    /*
    check if MYSQL Database exists & Set Some Details
    */
	private function setMysql(){
		$this->mysql = new \stdclass;
		$this->mysql->status = false;
		$this->mysql->tables = [];

        $query = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME =  ?";
        $db = DB::select($query, [$this->config["dbname"]]);
		
		if(isset($db[0])){
			$this->mysql->status = true;

			$ts = DB::select('SHOW TABLES');
			
			$tables_in_db = "Tables_in_" . $this->config["dbname"];
			foreach($ts AS $t){
				$this->mysql->tables[] = $t->$tables_in_db ;
			}

		}

		return $this;
	}

private function getMysql(){
	return $this->mysql;
}

public function execute($request, $viewer){
		
		$command = $request->input('command');
		$opt = json_decode($request->input('options'));		
		$resp = $this->validateCommand($command);

		if($resp){

			$function = StringHelper::camelCase($command); 
			
			if(Schema::hasTable('commands')){
			$newCommand = new Command(["command"=>$command, "options"=>$request->input('options'), "user_id"=>$viewer->user->id]);
			$newCommand->save();
			}

			$that = new static;
			
			$call = call_user_func([$that, $function], $opt);

			if(isset($call->error)){
				$request->session()->flash('error', 'There was a problem completing your request! ' . $call->error); 
			}else{
				$request->session()->flash('message', 'Task was successful! ' . $call->message); 
			}
			
		}else{
			$request->session()->flash('error', 'Check your spelling Mr. Developer. '.$command.' has not be registered. ' . json_encode(static::$commands));
			return false;
		}
	}
	
	public function validateCommand($command){
		
	if(in_array($command, $this->commands)){
   	 	return true;
   	 }else{
   	 	return false;
   	 }

	}

    private function setViewer(){
        $this->viewer = \App\User::getViewer();
       return $this;
    }

    private function makeChanges(){
        
        $this->changes = new DatabaseMutations($this->changes);

        return $this;
    }

    public function __get($key)
    {

        if (array_key_exists($key, $this->tables)) {
            return $this->tables[$key];
        }else{

	        $n = "get" . ucfirst($key);
	        if (method_exists($this, $n)) {
	            return $this->$n();
	        }
        }


        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $key .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
        
    }
}

//SAVED QUERIES

// SSELECT `id`,`date`,`key`,`transno`, COUNT(*) c FROM `ancienthead` GROUP BY `transno` HAVING c > 1;