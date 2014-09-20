<?php

namespace Seimas;

class Action extends \Eloquent implements ChildInterface {
	use DefaultParameterTrait;
	use ChildTrait;
	
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
		return $this->belongsTo('Seimas\Question', 'questions_id', 'id');
	}
	
}