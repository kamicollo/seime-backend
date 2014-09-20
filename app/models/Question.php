<?php

namespace Seimas;

class Question extends \Eloquent implements ChildInterface, ParentInterface {
	use ChildTrait;
	use ParentTrait;
	
	protected $fillable = [];
	protected $table = 'questions';
	protected $primaryKey = 'id';
	public $timestamps = false;
	public $childClass = 'Seimas\Action';
	public $childClasses = [
		Action::OTHER => 'Seimas\Action',
		Action::REGISTRATION => 'Seimas\Registration',
		Action::VOTE => 'Seimas\Vote',
		Action::SPEECH => 'Seimas\Speech',
		Action::UNANIMOUS_VOTE => 'Seimas\Action'
	];
	
	public function sitting() {
		return $this->belongsTo('Seimas\Sitting', 'sittings_id', $this->primaryKey);
	}
	
	public function subquestions() {
		return $this->hasMany('Seimas\Subquestion', 'questions_id', $this->primaryKey);
	}
	
	public function actions() {
		return $this->hasMany('Seimas\Action', 'questions_id', $this->primaryKey);
	}
	
	public function registrations() {
		return $this->hasMany('Seimas\Registration', 'questions_id', $this->primaryKey)
				->where('type', Action::REGISTRATION);
	}
	
	public function votes() {
		return $this->hasMany('Seimas\Vote', 'questions_id', $this->primaryKey)
				->where('type', Action::VOTE);
	}
	
	public function speeches() {
		return $this->hasMany('Seimas\Speech', 'questions_id', $this->primaryKey)
				->where('type', Action::SPEECH);
	}
	
	public function unanimousVotes() {
		return $this->hasMany('Seimas\Action', 'questions_id', $this->primaryKey)
				->where('type', Action::UNANIMOUS_VOTE);
	}
	
	public function items() {
		return $this->hasMany('Seimas\Item', 'questions_id', $this->primaryKey);
	}

	public function loadChildren() {
		$this->children = $this->actions()->orderBy('number', 'ASC')->get();
	}
	
	public function getChildClass($type = '') {
		if (array_key_exists($type, $this->childClasses)) {
			return $this->childClasses[$type];
		} else {
			return $this->childClass;
		}
	}
	
	public function setItemData(\Generator $items) {
		$this->itemData = $items;
	}
}