<?php

namespace Seimas\models;

class Session extends AbstractParentChild {
	
	protected $fillable = [];
	protected $table = 'sessions';
	protected $primaryKey = 'id';
	public $incrementing = false;
	public $timestamps = false;
	
	protected $childClass = 'Seimas\models\Sitting';
	
	public function sittings() {
		return $this->hasMany($this->childClass, 'sessions_id', $this->primaryKey);
	}
	
	public function loadChildren() {
		$this->children = $this->sittings()->orderBy('end_time', 'ASC')->get();
	}
	
	public function children() {
		return $this->sittings();
	}
	
	public function __parent() {
		return $this->cadency();
	}
	
	public function cadency() {
		return $this->belongsTo('Seimas\models\Cadency', 'kadencija', 'years');		
	}
}