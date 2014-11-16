<?php

namespace Seimas\factories;
use Log;

class ObjectFactory {
	
	public function resolve($object) {
		$class_name = get_class($object);
		return $this->resolveByClass($class_name);
	}
	
	public function resolveByClass($class_name) {
		if (array_key_exists($class_name, $this->map)) {
			$class = $this->map[$class_name];
			return new $class();
		} 
	}
	
}
