<?php

namespace Seimas\models;

abstract class AbstractParent extends SeimasModel implements ParentInterface, AncestorInterface {
	use ParentTrait;
	protected $childClass = 'Seimas\None';
	
	
	public function getDescendants() {
		return [$this->getChildren()];
	}
	
	public function getDescendantsData() {
		return [$this->getChildrenData()];
	}
	
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
	
	public function __sleep() {
		return array_merge(parent::__sleep(), ['childClass', 'children']);
	}
}
