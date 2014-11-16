<?php

namespace Seimas\models;

class Seimas implements AncestorInterface, ParentInterface  {
	use ParentTrait;
	
	protected $childClass = 'Seimas\models\Cadency';
	
	public function getDescendantFactory($type = null) {
		return function($class, $params) use ($type) {
			if ($class !== null) {
				$descendant = $this->createChild($class);
			} else {
				$descendant = $this->createChild($type);
			}
			foreach($params as $key => $value) {
				$descendant->$key = $value;
			}
			return $descendant;
		};
	}

	public function getDescendants() {
		return [$this->children];
	}

	public function getDescendantsData() {
		return [$this->getChildrenData()];
	}
	
	public function __get($property) {
		return null;
	}

}
