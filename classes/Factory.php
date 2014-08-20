<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Factory {
	
	static private $instance;
	static private $default_allowed_types = array('session' => 'Session', 'sitting' => 'Sitting', 'question' => 'Question', 'action' => 'Action');
	private $allowed_types;
	protected $DB = NULL;

	private function __construct($sql_params, $allowed_types) {
		
	/* populate known Object Types */
		$this->allowed_types = $allowed_types;
		
	/* initiate DB connection */
		try {
			list($dsn, $username, $password, $driver_options) = $sql_params;
			$this->DB = new DB($dsn, $username, $password, $driver_options); 			
		}
		catch (PDOException $e) {
			$this->DB = false;				
			trigger_error('Connection to DB failed with ' . $e->getMessage(), E_USER_WARNING);
		}	
	}
	
	static public function getInstance($sql_params, $allowed_types = array()) {
		if (empty($allowed_types)) $allowed_types = self::$default_allowed_types;
		if (empty(self::$instance)) {
			self::$instance = new Factory($sql_params, $allowed_types);			 
		}
		return self::$instance;
	}
	
	public function getObject($type, $url = NULL, $id = NULL, Seimas $parent = NULL, $parameters = array()) {
		if (isset($this->allowed_types[$type])) {
			$class_name = $this->allowed_types[$type];
			
			/* if url provided - initiate object without DB */
			if (false !== filter_var($url, FILTER_VALIDATE_URL)) {
				return new $class_name($url, $parent, $parameters, $this);
			}
			elseif (!empty($id)) {
				$class = new ReflectionClass($class_name);
				$sql = $class->getStaticPropertyValue('create_sql');
				$Object = $this->DB->createObject($sql, array($id), $class_name, array('', $parent, $parameters, $this));
				if ($Object instanceof Seimas) return $Object;
				else throw new Exception ('Object with the id provided was not found!');
			}
			else {
				throw new Exception('No object identifier (url / id) provided');
			}						
		}
		else {
			throw new Exception('unknown object type to be initiated');
		}
	}
	
	public function getObjectChildren($class, $child_type, $id, $parent, $parameters = array()) {
		//get urls and IDs of all children
		$class_name = $this->allowed_types[$child_type];
		try {
			$class = new ReflectionClass($class);
			$sql = $class->getStaticPropertyValue('children_sql');			
			$array = $this->DB->CreateObjects($sql, array($id), $class_name, array('', $parent, $parameters, $this));
			return $array;
		}
		catch (Exception $e) {
			echo $e->getMessage();				
		}
	}
	
	public function saveObject($table, $data, $excluded_keys = false) {
		
		/* try saving 1 object */
		try {
			return $this->DB->insertOne($table, $data, $excluded_keys);
		}
		catch (Exception $e) {
			echo $e->getMessage() . '<br>';						
		}
	}
		
	public function saveObjects($table, $data, $excluded_keys = false) {
		/* try saving many objects */
		try {
			$this->DB->insertMany($table, $data, $excluded_keys);
		}
		catch (Exception $e) {
			echo $e->getMessage();			
		}		
	}
	
	public function getArray($sql, $exec_params) {
		try {
			return $this->DB->getArray($sql, $exec_params);
		}
		catch (Exception $e) {
			echo $e->getMessage();
			echo $sql;
		}
	}
	
	public function getVar($sql, $exec_params) {
		try {
			return $this->DB->getVar($sql, $exec_params);
		}
		catch (Exception $e) {
			echo $e->getMessage();				
		}
	}
	
	public function showQueries() {
		$this->DB->showQueries();
	}
		
}

?>