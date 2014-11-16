<?php

namespace Seimas\models;

interface ParentInterface extends \IteratorAggregate {
	
	public function setupChildren($recursively = false);
	public function getChildSibling(ChildInterface $object, $offset);
	public function createChild($type = null);
	public function getChildren();
	public function getChildClass($type = null);
	public function setChildrenData(\Generator $data = null);
	
}
