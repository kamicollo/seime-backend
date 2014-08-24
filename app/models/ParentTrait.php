<?php

namespace Seimas;

trait ParentTrait {
	
	public $children = [];
	
	public function getIterator() {
		return $this->children->getIterator();
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
	
	
}
