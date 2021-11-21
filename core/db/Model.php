<?php 

class Model {

	/**
	 * tablename of model
	 * @var string
	 */
	protected $tablename;

	/**
	 * data of model
	 * @var array
	 */
	protected $data;

	/**
	 * primary key column of model table
	 * @var string
	 */
	public $pkColumn;


	/**
	 * initialise $tablename, $pkColumn and $data
	 * @param string 	$tablename 	table name
	 * @param array 	$values    	initial values
	 */
	public function __construct($tablename, $values = null, $debug = false) {
		$this->tablename = $tablename;

		$result = DB::query("DESCRIBE " . $this->tablename, $debug);

		
		while ($row = $result->fetch_assoc()) {
			$this->{$row['Field']} = null;
		}
      if(is_array($values)) {
         foreach ($values as $key => $value) {
            $this->{$key} = $value;
         }
      }

		$this->pkColumn = DB::getPrimaryKeyColumn($this->tablename, $debug);
	}

	/**
	 * get data from $data for undefined variable
	 * @param  string $varName 	name of variable
	 * @return mixed          	requested data
	 */
	public function __get($varName){
     	if (!array_key_exists($varName, $this->data)){
         	throw new Exception('column ' . $varName . ' does not exist');
      	} else {
      		return $this->data[$varName];
      	} 
   	}

   	/**
   	 * set undefined variable
   	 * @param string 	$varName name of variable
   	 * @param mixed 	$value   newvalue of variable
   	 */
   	public function __set($varName, $value){
         $this->data[$varName] = $value;
   	}

   	/**
   	 * save model to database
   	 */
   	public function save($debug = false) {
   		// if pkColumn is not set (aka new dataset) insert, otherwise update
        $data = $this->data;
        unset($data[$this->pkColumn]);
        if ($this->data[$this->pkColumn] == null) {
            DB::insert($this->tablename, $data, $debug);
            $this->data[$this->pkColumn] = DB::getInsertID();
   		} else {
            DB::update($this->tablename, $this->data[$this->pkColumn], $data, $debug);
   		}
   	}

   	/**
   	 * get model from database
   	 * @param  string $id id of model to get
   	 */
   	public function get($id = null, $column = null, $debug = false) {
   		if($id == null) {
            return $this->data;
         }
         if($column == null) {
            $column = $this->pkColumn;
         }
         $result = DB::select($this->tablename, '*', $column . " = '$id'", null, $debug);
         if(count($result) == 0) {
            $_SESSION["errors"][] = "invalid primary key value specified for table ".$this->tablename;
            return false;
         }
         $this->data = $result[0];
         return $this->data;
   	}

   	/**
   	 * delete model from database
   	 */
   	public function delete($debug = false) {
   		if ($this->data[$this->pkColumn] != null) {
   			DB::delete($this->tablename, $this->data[$this->pkColumn], $debug);
   		} else {
   			$_SESSION['errors'][] = "<p>delete failed: <br> no primary key for dataset defined</p>";
   		}
   	}
}