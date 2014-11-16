<?php

namespace Seimas\models;

trait ChildTrait {
	
	protected $_parent = null;
	protected $position = null;
	
	public function position() {
		return $this->position;
	}

	public function setParent(ParentInterface $object, $position) {
		$this->_parent = $object;
		$this->position = $position;
	}
	
	public function getSibling($offset) {
		return $this->_parent->getChildSibling($this, $offset);
	}
	
	public function getParent() {
		return $this->_parent;
	}
	
	public function hasParent() {
		return ($this->_parent !== null);
	}
	
	public function __sleep() {
		$this->_parent = null;
		return array_merge(parent::__sleep(), ['_parent', 'position']);
	}
}
