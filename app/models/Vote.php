<?php

namespace Seimas;


class Vote extends Action {
		
	const ACCEPT = 'accept';
	const REJECT = 'reject';
	const ABSTAIN = 'abstain';
	const NO_VOTE = 'not presen';
	const LEFT_MID_VOTE = 'disappeare';
	
	public static function validVoteRule() {
		return 'in:' . implode(',', [Vote::ABSTAIN,Vote::ACCEPT, Vote::LEFT_MID_VOTE, Vote::NO_VOTE, Vote::REJECT]);
	}
	
	public function members($voteType = null) {
		return	
			$this->defaultPivotParameter(
				$this->belongsToMany('Seimas\Member', 'votes', 'actions_id', 'members_id')
					->withPivot('fraction', 'vote'),
				'vote',
				$voteType,
				self::validVoteRule()
			);
	}
	
	public function registration() {
		return $this->belongsToMany('Seimas\Registration', 'voting_registration', 'voting_id', 'registration_id')
				->first();
	}
}