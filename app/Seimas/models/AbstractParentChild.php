<?php

namespace Seimas\models;

abstract class AbstractParentChild extends AbstractParent implements ChildInterface {
	use ChildTrait;
	
	public function __sleep() {
		$this->_parent = null;
		return array_merge(parent::__sleep(), ['_parent', 'position']);
	}
}
