<?php

namespace Seimas;

class Member extends \Eloquent {
	
	use DefaultParameterTrait;
	
	protected $fillable = [];
	protected $table = 'members';
	protected $primaryKey = 'id';
	public $timestamps = false;
	
	public function sittings($participated = null) {
		return	
			$this->defaultPivotParameter(
				$this->belongsToMany('Seimas\Sitting', 'sitting_participation', 'members_id', 'sittings_id')
					->withPivot('presence'),
				'presence',
				$participated,
				'boolean'
			);
	}
	
	public function sittingsWithData($participated = null) {
		return	
			$this->defaultPivotParameter(
				$this->belongsToMany('Seimas\Sitting', 'participation_data', 'members_id', 'sittings_id')
					->withPivot('official_presence', 'hours_present', 'hours_available'),
				'official_presence',
				$participated,
				'boolean'
			);
	}
	
	public function speeches() {
		return $this->belongsToMany('Seimas\Speech', 'speakers', 'members_id', 'actions_id');
	}
	
	public function votes($voteType = null) {
		return	
			$this->defaultPivotParameter(
				$this->belongsToMany('Seimas\Vote', 'votes', 'members_id', 'actions_id')
					->withPivot('fraction', 'vote'),
				'vote',
				$voteType,
				Vote::validVoteRule()
			);
	}
	
	public function registrations($presence = null) {
		return	
			$this->defaultPivotParameter(
				$this->belongsToMany('Seimas\Registration', 'registrations', 'members_id', 'actions_id')
					->withPivot('presence'),
				'presence',
				$presence,
				'boolean'
			);
	}
	
}