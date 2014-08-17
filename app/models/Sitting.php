<?php

namespace Seimas;

class Sitting extends \Eloquent {
	use DefaultParameterTrait;
	
	protected $fillable = [];
	protected $table = 'sittings';
	protected $primaryKey = 'id';
	public $timestamps = false;
	
	public function session() {
		return $this->belongsTo('Seimas\Session', 'sessions_id', 'id');
	}
	
	public function questions() {
		return $this->hasMany('Seimas\Question', 'sittings_id', $this->primaryKey);
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
}