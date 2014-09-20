<?php

namespace Seimas;

class Sitting extends \Eloquent implements ChildInterface, ParentInterface {
	use DefaultParameterTrait;
	use ChildTrait;
	use ParentTrait;
	
	protected $fillable = [];
	protected $table = 'sittings';
	protected $primaryKey = 'id';
	public $timestamps = false;
	protected $childClass = 'Seimas\Question';
	
	public function session() {
		return $this->belongsTo('Seimas\Session', 'sessions_id', 'id');
	}
	
	public function questions() {
		return $this->hasMany($this->childClass, 'sittings_id', $this->primaryKey);
	}
	
	public function members($participated = null) {
		return	
			$this->defaultPivotParameter(
				$this->belongsToMany('Seimas\Member', 'sitting_participation', 'sittings_id', 'members_id')
					->withPivot('presence'),
				'presence',
				$participated,
				'boolean'
			);
	}
	
	public function membersWithData($participated = null) {
		return	
			$this->defaultPivotParameter(
				$this->belongsToMany('Seimas\Member', 'participation_data', 'sittings_id', 'members_id')
					->withPivot('official_presence', 'hours_available', 'hours_present'),
				'official_presence',
				$participated,
				'boolean'
			);
	}

	public function loadChildren() {
		$this->children = $this->questions()->orderBy('number', 'ASC')->get();
	}

}