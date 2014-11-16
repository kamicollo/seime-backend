<?php

namespace Seimas\models;

trait ParentTrait {
	/** @var array */
	public $children = [];
	/** @var \Seimas\DescendantBag */
	protected $childrenData = null;
	
	public function getIterator() {
		if ($this->children instanceof \IteratorAggregate) {
			return $this->children->getIterator();
		} else {
			return new \EmptyIterator();
		}
	}
	
	public function setupChildren($recursively = false) {
		$this->loadChildren();
		$position = 0;
		$this->children->each(function($child) use (&$position) {
			$child->setParent($this, $position++);
		});
		if ($recursively) {
			$this->children->each(function($child) {
				if ($child instanceof ParentInterface) {
					$child->setupChildren(true);
				}
			});
		}
	}

	public function getChildSibling(ChildInterface $object, $offset) {
		//check if the object passed is indeed a child in our tree
		if ($object === $this->children->offsetGet($object->position())) {
			//check if a sibling exists
			if ($this->children->offsetExists($object->position() + $offset)) {
				//return the sibling
				return $this->children->offsetGet($object->position() + $offset);
			} else {
				//else return null
				return null;
			}
		}
	}
	
	public function createChild($type = null) {
		$class = $this->getChildClass($type);
		$child = new $class();
		$this->children[] = $child;
		$child->setParent($this, count($this->children) - 1);
		return $child;
	}
	
	public function getChildren() {
		return $this->children;
	}
	
	public function getChildClass($type = null) {
		return $this->childClass;
	}
	
	public function setChildrenData(\Generator $data = null) {
		if ($data !== null) {
			$this->childrenData = new \Seimas\DescendantBag($this->getChildClass(), $data);
		}
	}
	
	/** @return \Generator */
	public function getChildrenData() {
		return $this->childrenData;
	}
	/** @return boolean */
	public function hasChildrenData() {
		if ($this->getChildrenData() instanceof \Seimas\DescendantBag) {
			return $this->getChildrenData()->getGenerator()->valid();
		} else {
			return false;
		}
	}
	
	public function __sleep() {
		$this->childrenData = null;
		return array_merge(parent::__sleep(), ['children']);
	}
	
}
