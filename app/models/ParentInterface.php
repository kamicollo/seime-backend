<?php

namespace Seimas;

interface ParentInterface extends \IteratorAggregate {
	
	public function loadChildren();
	public function setupChildren($recursive = false);
	public function getChildSibling(ChildInterface $object, $position);
	public function createChild();
	public function getChildClass();
}
