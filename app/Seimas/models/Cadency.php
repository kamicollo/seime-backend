<?php

namespace Seimas\models;

class Cadency extends AbstractParentChild {
	
	protected $fillable = [];
	protected $table = 'cadencies';
	protected $primaryKey = 'id';
	public $timestamps = false;
	protected $childClass = 'Seimas\models\Session';
	
	public function sittings() {
		return $this->hasMany($this->childClass, 'kadencija', 'years');
	}
	
	public function loadChildren() {
		$this->children = $this->sessions()->orderBy('end_time', 'ASC')->get();
	}
	public function __parent() {
		return null;
	}
}