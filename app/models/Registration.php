<?php

namespace Seimas;


class Registration extends Action {
		
	public function members($presence = null) {
		return	
			$this->defaultPivotParameter(
				$this->belongsToMany('Seimas\Member', 'registrations', 'actions_id', 'members_id')
					->withPivot('presence'),
				'presence',
				$presence,
				'boolean'
			);
	}
	public function votes() {
		return $this->belongsToMany('Seimas\Vote', 'voting_registration', 'registration_id', 'voting_id');
	}
}

