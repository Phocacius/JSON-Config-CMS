<?php 
class DB {

	/**
	 * stores established database connection
	 * @var mysqli connection
	 */
	public static $db = null;

	/**
	 * id of last inserted row
	 * @var string
	 */
	private static $insertID;


	/**
	 * initialise db connection
	 * @param  string 	$dbHost 	db host
	 * @param  string 	$dbUser 	db user
	 * @param  string 	$dbPassword db password
	 * @param  string 	$dbName     db name
	 */
	public static function init($dbHost, $dbUser, $dbPassword, $dbName, $use4ByteUtf8 = false) {
		self::$db = new mysqli($dbHost, $dbUser, $dbPassword, $dbName);
		if (self::$db->connect_errno) {
			$_SESSION['errors'][] = "MySQL connection failed: ". self::$db->connect_error;
		}
		self::$db->query($use4ByteUtf8 ? "SET NAMES utf8mb4;" : "SET NAMES utf8;");
	}

	/**
	 * querys sql on database
	 * @param  string $sql sql to query
	 * @return mixed      result set
	 */
	public static function query($sql, $debug = false) {
		$result = self::$db->query($sql);
		if ($debug) {
			$_SESSION['debug'][] = __FUNCTION__ . ': $sql is <strong>'.$sql.'</strong>';
		}
		if (self::$db->errno) {
			$_SESSION['errors'][] = "<p>insert failed: " . self::$db->error . "<br> statement was: <strong> $sql </strong></p>";
		}
		return $result;
	}
	/**
	 * querys sql and returns an associative array
	 * @param  [type] $sql sql to query
	 * @return  array result
	 */
	public static function queryArray($sql, $debug = false) {
		$result = self::$db->query($sql);
		if ($debug) {
			$_SESSION['debug'][] = __FUNCTION__ . ': $sql is <strong>'.$sql.'</strong>';
		}
		$ret = array();
		if(!$result) {
			$_SESSION['errors'][] = "Query failed: ". self::$db->error . "<br> Statement was: $sql";
			return array();
		}
		while ($row = $result->fetch_assoc()) {
			$ret[] = $row;
		}
		return $ret;
	}

	/**
	 * insert an entry to the specified table
	 * @param  string 	$table 	database table to insert into
	 * @param  array 	$data  	data to insert
	 */
	public static function insert($table, $data, $debug = false): bool {
		$keys = ""; $values = "";
		foreach ($data as $key => $value) {
			$key = self::escape($key);
			$value = self::escape($value);
			$keys .= "".$key.", ";
			if($value == null) {$values .= "null, ";}
			else {$values .= "'".$value."', ";}
		}
		$keys = rtrim($keys, ', ');
		$values = rtrim($values, ', ');

		$sql = "INSERT INTO $table (".$keys.") VALUES (".$values.")";

		if ($debug) {
			$_SESSION['debug'][] = __FUNCTION__ . ': $sql is <strong>'.$sql.'</strong>';
		}

		self::$db->query($sql);

		if (self::$db->errno) {
			$_SESSION['errors'][] = "<p>insert failed: " . self::$db->error . "<br> statement was: <strong> $sql </strong></p>";
			return false;
		}

		self::$insertID = self::$db->insert_id;
		return true;
	}

	/**
	 * update an entry in specified table
	 * @param  string 	$table 	database table to update
	 * @param  string 	$id    	id of dataset to update
	 * @param  array 	$data  	updated data
	 */
	public static function update($table, $id, $data, $debug = false): bool {
		$sql = "UPDATE $table SET ";
		foreach ($data as $key => $value) {
			$key = self::escape($key);
			$value = self::escape($value);
			if($value == null) {$sql .= "$key = null, ";}
			else {$sql .= "$key = '$value', ";}
		}
		// strip trailing comma
		$sql = rtrim($sql, ", ");

		$sql .= " WHERE " . self::getPrimaryKeyColumn($table) . " = $id";

		if ($debug) {
			$_SESSION['debug'][] = __FUNCTION__ . ': $sql is <strong>'.$sql.'</strong>';
		}

		self::$db->query($sql);

		if (self::$db->errno) {
			$_SESSION['errors'][] = "<p>update failed: " . self::$db->error . "<br> statement was: <strong> $sql </strong></p>";
			return false;
		}
		return true;
	}

	/**
	 * select data from specified table
	 * @param  string 	$table   	database table to select from
	 * @param  array 	$columns 	colums to select, default all
	 * @param  array 	$where   	where condition
	 * @param  string 	$limit   	limit 
	 * @return array          		fetched data
	 */
	public static function select($table, $columns = '*', $where = null, $limit = null, $debug = false) {
		$sql = "SELECT " . self::generateColumnList($columns) . " FROM $table";
		if ($where != null) {
			$sql .= " WHERE ".$where;
		}
		if ($limit != null) {
			$sql .= " LIMIT ".$limit;
		}
		if ($debug) {
			$_SESSION['debug'][] = __FUNCTION__ . ': $sql is <strong>'.$sql.'</strong>';
		}

		$result = self::$db->query($sql);
		if (self::$db->errno) {
			$_SESSION['errors'][] = "<p>select failed: " . self::$db->error . "<br> statement was: <strong> $sql </strong></p>";
			return array();
		} else {
			$ret = array();
			while ($row = $result->fetch_assoc()) {
				$ret[] = $row;
			}
			return $ret;
		}
	}

	/**
	 * delete data from specified table
	 * @param  string $table database table to delete from
	 * @param  string $id    id of dataset to delete
	 */
	public static function delete($table, $id, $debug = false) {
		$sql = "DELETE FROM $table WHERE " . self::getPrimaryKeyColumn($table) . " = $id";

		if ($debug) {
			$_SESSION['debug'][] = __FUNCTION__ . ': $sql is <strong>'.$sql.'</strong>';
		}

		self::$db->query($sql);

		if (self::$db->errno) {
			$_SESSION['errors'][] = "<p>delete failed: " . self::$db->error . "<br> statement was: <strong> $sql </strong></p>";
		}
	}

	/**
	 * return insert id
	 * @return string self::$insertID
	 */
	public static function getInsertID() {
		return self::$insertID;
	}

	/**
	 * escape given string
	 * @param  string 	$string string to escape
	 * @return string         	escaped string
	 */
	public static function escape($string) {
		return self::$db->real_escape_string($string);
	}

	/**
	 * get primary key column from given table
	 * @param  string 	$table 	table to get primary key from
	 * @return string        	primary key column name
	 */
	public static function getPrimaryKeyColumn($table, $debug = false) {
		$sql = "SHOW KEYS FROM $table WHERE key_name = 'PRIMARY'";

		if ($debug) {
			$_SESSION['debug'][] = __FUNCTION__ . ': $sql is <strong>'.$sql.'</strong>';
		}
		
		$result = self::$db->query($sql);

		if ($row = $result->fetch_assoc()) {
			return $row['Column_name'];
		}

		if (self::$db->errno) {
			$_SESSION['errors'][] = "<p>getPrimaryKeyColumn failed: " . self::$db->error . "<br> statement was: <strong> $sql </strong></p>";
		}

		return false;
	}

	/**
	 * generates list of columns from array
	 * @param  array 	$columns 	array of columns
	 * @return string          		imploded array
	 */
	private static function generateColumnList($columns) {
		if (is_array($columns)) {
			return implode(', ', $columns);
		} else {
			return $columns;
		}
		
	}

	/**
	 * parses a given date string (from MYSQL datetime) and returns only the date
	 * @param  string (datetime)  $date
	 * @param  boolean $addHour indicates whether an hour should be added to the timestamp
	 * @return string          the date
	 */
	public static function parseDate($date, $addHour = false) {
		if($date != null && $date != "0000-00-00 00:00:00") {
			$timestamp = strtotime($date);
		} else {
			$timestamp = time();
			if($addHour) {$timestamp += 3600;}
		}
		return date("Y-m-d", $timestamp);		
	}

	public static function parseTime($date, $addHour = false) {

		if($date != null && $date != "0000-00-00 00:00:00") {
			$timestamp = strtotime($date);
		} else {
			$timestamp = time();
			if($addHour) {$timestamp += 3600;}
		}
		return date("H:i", $timestamp);	

	}

	public static function writeDateToDB($date, $time) {
		$timestamp = strtotime($date);
		$timestamp = strtotime($time, $timestamp);
		return date("Y-m-d H:i:s", $timestamp);
	}

	public static function getFormattedDate($date) {
		$timestamp = strtotime($date);
		return strftime("%d. %B %Y um %H:%M", $timestamp);
	}

    /**
     * @param array $components individual where clauses that are to be AND combined. The parts need to be escaped
     * @return string empty string if components are empty, otherwise a part of an SQL clause starting with WHERE that
     * contains all components.
     */
    public static function getWhereClauses(array $components): string {
        if(count($components) === 0) {
            return "";
        }
        return " WHERE ".implode(" AND ", $components);
    }
}