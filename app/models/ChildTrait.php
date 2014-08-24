<?php

namespace Seimas;

trait ChildTrait {
	
	protected $parent = null;
	protected $position = null;
	
	public function position() {
		return $this->position;
	}

	public function setParent(ParentInterface $object, $position) {
		$this->parent = $object;
		$this->position = $position;
	}
	
	public function getSibling($offset) {
		return $this->parent->getChildSibling($this, $offset);
	}
}
