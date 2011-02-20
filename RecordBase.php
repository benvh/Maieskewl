<?php
/*
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE" (Revision 42):
 * <ben(at)pantheonserver.com> wrote this file. As long as you retain this notice you
 * can do whatever you want with this stuff. If we meet some day, and you think
 * this stuff is worth it, you can buy me a beer in return Ben
 * ----------------------------------------------------------------------------
 */
include "Database.php";

class RecordBase {
	
	protected $fields = array();
	private $table;
	
	private $dirty;
	private $dirtyFields = array();
	
	public function RecordBase() {
		$this->fields = Database::getInstance()->getFields(get_class($this));
		foreach($this->fields as $field) {
			$this->fields[$field] = NULL;
		}
		$this->dirty = false;
		$this->id = 0;
	}
	
	private function fieldExists($field) {
		return array_key_exists($field, $this->fields);
	}
	
	public function __get($name) {
		if(!$this->fieldExists($name)) return;
		return $this->fields[$name];
	}

	public function __set($name, $value) {
		if(!$this->fieldExists($name)) return;
		$this->fields[$name] = $value;
		
		$this->dirty = true;
		array_push($this->dirtyFields, $name);
	}
	
	public function getTable() {
		if(!isset($this->table)) $this->table = strtolower(String::pluralize(get_class($this)));
		return $this->table;
	}
	
	public function save() {
		if(!$this->dirty) return;
	
		$res = Database::query("SELECT * FROM " .  $this->getTable() . " WHERE id=" . $this->id);
		switch(count($res)) {
			case 0:
				foreach($this->dirtyFields as $field) {
					$values[] = $this->$field;
				}
				$id = Database::query("INSERT INTO " . $this->getTable() . "(" . implode(",", $this->dirtyFields) . ") VALUES(" . implode(",", array_fill(0, count($this->dirtyFields), "?")) .")",
									  $values);					
				$this->id = $id;
				$this->undirty();
				break;
				
			case 1:
				$values = array();
				foreach($this->dirtyFields as $field) {
					if(is_string($this->$field)) {
						array_push($values, $field . "=" . "'" . $this->$field . "'");
					} else {
						array_push($values, $field . "=" . $this->$field);
					}
				}
				
				Database::query("UPDATE " . $this->getTable() . " SET " . implode(",", $values) . " WHERE id=" . $this->id);
				$this->undirty();
				break;
		}
	}
	
	public function undirty() {
		$this->dirty = false;
		$this->dirtyFields = array();
	}
}
?>