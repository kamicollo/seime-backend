<?php

namespace Seimas;

interface ChildInterface {
	
	public function position();
	public function setParent(ParentInterface $object, $position);
	public function getSibling($offset);
	
}
