<?php

namespace Seimas\models;

interface ChildInterface {
	
	public function position();
	public function setParent(ParentInterface $object, $position);
	public function getSibling($offset);
	public function getParent();
	public function __parent();
	
}
