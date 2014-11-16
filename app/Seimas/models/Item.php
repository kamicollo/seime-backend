<?php

namespace Seimas\models;

class Item extends AbstractParentChild {
	
	protected $fillable = [];
	protected $table = 'items';
	protected $primaryKey = 'id';
	public $timestamps = false;
	protected $childClass = 'Seimas\models\Presenter';
	
	public function question() {
		return $this->belongsTo('Seimas\models\Question', 'questions_id', $this->primaryKey);
	}
	
	public function __parent() {
		return $this->question();
	}
	
	public function presenters() {
		return $this->hasMany($this->childClass, 'items_id', $this->primaryKey);
	}
	
}