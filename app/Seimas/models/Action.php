<?php

namespace Seimas\models;

class Action extends AbstractChild {
	use DefaultParameterTrait;
	
	protected $fillable = [];
	protected $table = 'actions';
	protected $primaryKey = 'id';
	public $timestamps = false;
	
	const REGISTRATION = 'registration';
	const SPEECH = 'speech';
	const VOTE = 'voting';
	const UNANIMOUS_VOTE = 'u_voting';
	const ALTERNATE_VOTE = 'a_voting';
	const OTHER = 'other';
	const VOTE_OUTCOME_ACCEPT = 'accepted';
	const VOTE_OUTCOME_REJECT = 'rejected';
	const VOTE_OUTCOME_UNKNOWN = 'no_outcome';
	
	public function question() {
		return $this->belongsTo('Seimas\models\Question', 'questions_id', 'id');
	}
	
	public function __parent() {
		return $this->question();
	}
	
}