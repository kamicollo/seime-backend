<?php

namespace Seimas\models;

class Sitting extends AbstractParentChild {
	use DefaultParameterTrait;
	
	protected $fillable = [];
	protected $table = 'sittings';
	protected $primaryKey = 'id';
	public $incrementing = false;
	public $timestamps = false;
	protected $childClass = 'Seimas\models\Question';
	protected $participationData = null;
	public $participants = null;
	public $date = null;
	
	public function session() {
		return $this->belongsTo('Seimas\models\Session', 'sessions_id', 'id');
	}
	
	public function __parent() {
		return $this->session();
	}
	
	public function questions() {
		return $this->hasMany($this->childClass, 'sittings_id', $this->primaryKey);
	}
	
	public function members($participated = null) {
		return	
			$this->defaultPivotParameter(
				$this->belongsToMany('Seimas\models\Member', 'sitting_participation', 'sittings_id', 'members_id')
					->withPivot('presence'),
				'presence',
				$participated,
				'boolean'
			);
	}
	
	public function membersWithData($participated = null) {
		return	
			$this->defaultPivotParameter(
				$this->belongsToMany('Seimas\models\Member', 'participation_data', 'sittings_id', 'members_id')
					->withPivot('official_presence', 'hours_available', 'hours_present'),
				'official_presence',
				$participated,
				'boolean'
			);
	}

	public function loadChildren() {
		$this->children = $this->questions()->orderBy('number', 'ASC')->get();
	}
	
	public function setParticipationData(\Generator $data = null) {
		$this->participationData = $data;
	}
	
	public function getDescendantsData() {
		$array = parent::getDescendantsData();
		if ($this->participationData !== null) {
			$array[] = new \Seimas\DescendantBag('SittingParticipation', $this->participationData);
		}
		return $array;
	}
	
	public function createChild($type = null) {
		if ($type == 'SittingParticipation') {
			return $this;
		}
		return parent::createChild($type);
	}
	
	public function save(array $options = array(), $recursively = false) {
		parent::save($options, $recursively);
		$this->saveParticipationData();
	}
	
	public function saveParticipationData() {
		foreach($this->participants as $member) {
			$this->members()->save(
				$member,
				['presence' => $this->participants->offsetGet($member)]
			);
		}
	}
	
	public function __sleep() {
		$this->participationData = null;
		return array_merge(parent::__sleep(), ['participants', 'date']);
	}

}