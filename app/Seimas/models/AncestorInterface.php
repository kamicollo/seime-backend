<?php

namespace Seimas\models;

interface AncestorInterface {
	/** @var \Seimas\DescendantBag[] */
	public function getDescendantsData();
	/** @var \Illuminate\Database\Eloquent[] */
	public function getDescendants();
	/** @var \Closure */
	public function getDescendantFactory($type = null);
	
}
