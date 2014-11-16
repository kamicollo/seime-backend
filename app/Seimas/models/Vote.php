<?php

namespace Seimas\models;


class Vote extends UnanimousVote implements AncestorInterface {
		
	const ACCEPT = 'accept';
	const REJECT = 'reject';
	const ABSTAIN = 'abstain';
	const NO_VOTE = 'not presen';
	const LEFT_MID_VOTE = 'disappeare';
	/** @var \Generator */
	protected $voteData = null;
	public $vote_data = null;
	public $total_counts = [];
	
	public static function validVoteRule() {
		return 'in:' . implode(',', [Vote::ABSTAIN,Vote::ACCEPT, Vote::LEFT_MID_VOTE, Vote::NO_VOTE, Vote::REJECT]);
	}
	
	public function members($voteType = null) {
		return	
			$this->defaultPivotParameter(
				$this->belongsToMany('Seimas\models\Member', 'votes', 'actions_id', 'members_id')
					->withPivot('fraction', 'vote'),
				'vote',
				$voteType,
				self::validVoteRule()
			);
	}
	
	public function registration() {
		return $this->belongsToMany('Seimas\models\Registration', 'voting_registration', 'voting_id', 'registration_id')
				->first();
	}
	
	public function setVoteData(\Generator $data) {
		$this->voteData = $data;
	}
	
	public function getDescendantsData() {
		if ($this->voteData !== null) {
			$array[] = new \Seimas\DescendantBag('VoteData', $this->voteData);
		}
		return $array;
	}

	public function getDescendantFactory($type = null) {
		return function() {
			return $this;
		};
	}

	public function getDescendants() {
		return [];
	}
	
	public function save(array $options = array(), $recursively = false) {
		parent::save($options, $recursively);
		$this->saveVoteData();
	}
	
	public function saveVoteData() {
		foreach($this->vote_data as $member) {
			$this->members()->save(
				$member,
				$this->vote_data->offsetGet($member)
			);
		}
	}
	
	public function __sleep() {		
		$this->voteData = null;
		return array_merge(parent::__sleep(), ['vote_data', 'total_counts']);
	}

}