<?php

namespace Seimas\models;

class Question extends AbstractParentChild {
	
	protected $fillable = [];
	protected $table = 'questions';
	protected $primaryKey = 'id';
	public $timestamps = false;
	public $childClass = 'Seimas\models\Action';
	public $incrementing = false;
	public $childClasses = [
		Action::OTHER => 'Seimas\models\Action',
		Action::REGISTRATION => 'Seimas\models\Registration',
		Action::VOTE => 'Seimas\models\Vote',
		Action::SPEECH => 'Seimas\models\Speech',
		Action::UNANIMOUS_VOTE => 'Seimas\models\UnanimousVote',
		'Seimas\models\Item' => 'Seimas\models\Item',
	];
	public $item_children = [];
	protected $itemData = null;
	
	public function sitting() {
		return $this->belongsTo('Seimas\models\Sitting', 'sittings_id', $this->primaryKey);
	}
	
	public function __parent() {
		return $this->sitting();
	}
	
	public function subquestions() {
		return $this->hasMany('Seimas\models\Subquestion', 'questions_id', $this->primaryKey);
	}
	
	public function actions() {
		return $this->hasMany('Seimas\models\Action', 'questions_id', $this->primaryKey);
	}
	
	public function registrations() {
		return $this->hasMany('Seimas\models\Registration', 'questions_id', $this->primaryKey)
				->where('type', Action::REGISTRATION);
	}
	
	public function votes() {
		return $this->hasMany('Seimas\models\Vote', 'questions_id', $this->primaryKey)
				->where('type', Action::VOTE);
	}
	
	public function speeches() {
		return $this->hasMany('Seimas\models\Speech', 'questions_id', $this->primaryKey)
				->where('type', Action::SPEECH);
	}
	
	public function unanimousVotes() {
		return $this->hasMany('Seimas\models\Action', 'questions_id', $this->primaryKey)
				->where('type', Action::UNANIMOUS_VOTE);
	}
	
	public function items() {
		return $this->hasMany('Seimas\models\Item', 'questions_id', $this->primaryKey);
	}

	public function loadChildren() {
		$this->children = $this->actions()->orderBy('number', 'ASC')->get();
	}
	
	public function getChildClass($type = null) {
		if (array_key_exists($type, $this->childClasses)) {
			return $this->childClasses[$type];
		} else {
			return $this->childClass;
		}
	}
	
	public function setItemData(\Generator $items) {
		$this->itemData = $items;
	}
	
	public function createChild($type = null) {
		$class = $this->getChildClass($type);
		$child = new $class();
		if ($class === 'Seimas\models\Item') {
			$this->item_children[] = $child;
			$child->setParent($this, count($this->item_children) - 1);
		} else {
			$this->children[] = $child;
			$child->setParent($this, count($this->children) - 1);
		}
		return $child;
	}
	
	public function getDescendantsData() {
		$array = parent::getDescendantsData();
		if ($this->itemData !== null) {
			$array[] = new \Seimas\DescendantBag('Seimas\models\Item', $this->itemData);
		}
		return $array;
	}

	public function getDescendants() {
		$ch = parent::getDescendants();
		$ch[] = $this->item_children;
		return $ch;
	}
	
	public function __sleep() {		
		$this->itemData = null;
		return array_merge(parent::__sleep(), ['item_children', 'childClasses']);
	}
	
}