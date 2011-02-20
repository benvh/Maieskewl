<?php
/*
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE" (Revision 42):
 * <ben(at)pantheonserver.com> wrote this file. As long as you retain this notice you
 * can do whatever you want with this stuff. If we meet some day, and you think
 * this stuff is worth it, you can buy me a beer in return Ben
 * ----------------------------------------------------------------------------
 */

include "String.php";

class Database {
	
	private static $instance;
	
	private $settings;
	private $connected = false;
	private $conn;
	
	private $modelFields;
	
	public static function getInstance() {
		if(!isset($instance)) {
			$instance = new Database();
			$settings = parse_ini_file("c.ini");
			
			$instance->settings = $settings;
		}
		return $instance;
	}
	
	private function connect() {
		if($this->isConnected()) return;
		
		$this->conn = mysqli_connect(localhost, $this->settings["username"], $this->settings["password"], $this->settings["database"]) or die("Could not connect to database: " . mysqli_connect_error());
		$this->connected = true;
	}
	
	public function isConnected() {
		if(!isset($this->connected)) return false;
		return $this->connected;
	}
	
	public function &getConnection() {
		return $this->conn;
	}
	
	public function getFields($class) {
		if(!$this->isConnected()) $this->connect();
		
		if(isset($this->modelFields[$class])) return $this->modelFields[$class];

		if( $res = $this->conn->query("SHOW COLUMNS FROM " . strtolower(String::pluralize($class))) ) {
			$fields = array();
			while( $row = $res->fetch_assoc() ) {
				array_push($fields, $row["Field"]);
			}
			$res->close();
			return $fields;
		}
		
		return NULL;
	}
	
	private static function checkConnection() {
		if(!Database::getInstance()->isConnected()) Database::getInstance()->connect();
	}
	
	public static function find($class, $id) {
		Database::checkConnection();
		
		if(!is_numeric($id)) return NULL;
		
		$cols = Database::query("SELECT * FROM " . strtolower(String::pluralize($class)) . " WHERE id=?", array($id));
		if(!isset($cols) || count($cols) <= 0) return NULL;
		
		$obj = new $class;
		foreach(array_keys($cols[0]) as $col) {
			$obj->$col = $cols[0][$col];
		}
		
		
		return $obj;
	}
	
	private function pquery($sql, $params) {
		if(!$this->isConnected()) $this->connect();
		
		if(count($params) > 0) {
			$stmt = $this->conn->prepare($sql);
			$strtypes = array();
			foreach($params as $param) array_push($strtypes, substr(gettype($param), 0, 1));
			
			call_user_func_array(array($stmt, 'bind_param'), array_merge(array(implode("", $strtypes)), $this->refValues($params)));
			$stmt->execute();
			
			if(preg_match("/^INSERT INTO .*/i", $sql) > 0 || preg_match("/^UPDATE .*/i", $sql) > 0) {
				$stmt->close();
				
				return $this->conn->insert_id;
			}
			
			$meta = $stmt->result_metadata();
			while($field = $meta->fetch_field()) {
				$parameters[] = &$row[$field->name];
			}
		
			call_user_func_array(array($stmt, 'bind_result'), $this->refValues($parameters));
			while($stmt->fetch()) {
				$rows[] = $row;
			}
			$stmt->close();
			return $rows;
		} else {
			if($res = $this->conn->query($sql)) {
				while($row = $res->fetch_assoc()) {
					$rows[] = $row;
				}
				return $rows;
			}
		}
		
		return NULL;
		
	}
	
	private function refValues($arr){
       if (strnatcmp(phpversion(),'5.3') >= 0) //Reference is required for PHP 5.3+
       {
           $refs = array();
           foreach($arr as $key => $value)
               $refs[$key] = &$arr[$key];
           return $refs;
       }
       return $arr;
    }
	
	public static function query($sql, $params=array()) {
		return Database::getInstance()->pquery($sql, $params);
	}
}

?>