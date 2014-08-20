<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


class DB extends PDO {
	
	private $queries;
	
	public function __construct($dsn, $username, $passwd, $options) {
		parent::__construct($dsn, $username, $passwd, $options);
	}
	
	public function createObject($sql_to_prepare, $exec_params, $class_name, $construct_params = array()) {
		$start_time = microtime(true);
		$q = parent::prepare($sql_to_prepare);
		$q->setFetchMode(PDO::FETCH_CLASS, $class_name, $construct_params);
		$q->execute($exec_params) or $this->throwError($q);
		$this->logQuery(func_get_args(), $start_time, microtime(true));
		return $q->fetch(PDO::FETCH_CLASS);
	}
	
	public function createObjects($sql_to_prepare, $exec_params, $class_name, $construct_params = array()) {
		$start_time = microtime(true);
		$q = parent::prepare($sql_to_prepare);		
		$q->setFetchMode(PDO::FETCH_CLASS, $class_name, $construct_params);
		$q->execute($exec_params) or $this->throwError($q);
		$array = array();
		while ($object = $q->fetch(PDO::FETCH_CLASS)) {			
			$array[$object->getId()] = clone $object;
		}
		$this->logQuery(func_get_args(), $start_time, microtime(true));
		return $array;	
	}
		
	
	public function getArray($sql_to_prepare, $exec_params) {
		$start_time = microtime(true);
		$q = parent::prepare($sql_to_prepare);
		$q->execute($exec_params) or $this->throwError($q);
		$this->logQuery(func_get_args(), $start_time, microtime(true));
		return $q->fetchAll(PDO::FETCH_ASSOC);
	}
	
	public function getVar($sql_to_prepare, $exec_params) {
		$start_time = microtime(true);
		$q = parent::prepare($sql_to_prepare);
		$q->execute($exec_params) or $this->throwError($q);
		//$q->debugDumpParams();
		$this->logQuery(func_get_args(), $start_time, microtime(true));
		$a = $q->fetch();
		return $a[0];
	}
	
	protected function prepareInsert($table, $keys, $excluded_keys) {
		$update_fields = '';
		if (false !== $excluded_keys) {			
			list($keys, $placeholders, $update_fields) = $this->getPlaceholders($keys, $excluded_keys);			
		}
		else {
			list($keys, $placeholders) = $this->getPlaceholders($keys, $excluded_keys);
		}
		if (empty($update_fields)) $on_duplicate = '';
		else $on_duplicate = 'ON DUPLICATE KEY UPDATE ' . $update_fields;		
		$sql = "INSERT INTO `$table` $keys VALUES $placeholders $on_duplicate";		
		//print_f($sql);
		$q = parent::prepare($sql);
		return $q;
	}
	
	public function insertOne($table, $data, $excluded_keys = false) {
		$start_time = microtime(true);
		$q = $this->prepareInsert($table, array_keys($data), $excluded_keys);
		foreach ($data as $key => $value) {			
			$q->bindValue(':' . $key, $value);
		}		
		$q->execute() or $this->throwError($q);		
		$this->logQuery(func_get_args(), $start_time, microtime(true));
		return $this->lastInsertId();
	}
	
	public function insertMany($table, $data, $excluded_keys = false) {
		
		if (!isset($data[0])) throw new Exception('empty data set provided!');
		
		$start_time = microtime(true);
		$q = $this->prepareInsert($table, array_keys($data[0]), $excluded_keys);
		foreach ($data as $row) {
			foreach ($row as $key => $value) {
				$q->bindValue(':' . $key, $value);
			}
			$q->execute() or $this->throwError($q);			
		}
		$this->logQuery(func_get_args(), $start_time, microtime(true));
	}
	
	protected function throwError($handler) {
		$error = $handler->errorInfo();
		throw new Exception($error[2]);
		return true;
	}
	
	protected function getPlaceholders($keys, $excluded_keys) {
		$key_brackets = '(`' . implode('`, `', $keys) . '`)';
		$pl_brackets = '(:' . implode(', :', $keys) . ')';
		if (false === $excluded_keys) {
			return array($key_brackets, $pl_brackets);
		}
		else {
			$update_fields = array();
			foreach ($keys as $key) {
				if (!in_array($key, $excluded_keys)) {
					$update_fields[] = "`$key` = VALUES(`$key`)";
				}
			}
			$update_fields = implode(', ', $update_fields);
			return array($key_brackets, $pl_brackets, $update_fields);
		}		
	}
	
	protected function logQuery($args, $start_time, $end_time) {
		$this->queries[] = array('length' => round(($end_time - $start_time) * 1000, 3) ,'args' => $args[0]);
	}
	
	public function showQueries() {
		print_f($this->queries);
	}
	
	public function exec($sql) {
		$a = parent::exec($sql);
		if (false === $a) {
			print_f($this->errorInfo());			
		}
	}
}
?>
