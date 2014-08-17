<?php

namespace Seimas;

class Action extends \Eloquent {
	use DefaultParameterTrait;
	
	protected $fillable = [];
	protected $table = 'actions';
	protected $primaryKey = 'id';
	public $timestamps = false;
	
	const REGISTRATION = 'registration';
	const SPEECH = 'speech';
	const VOTE = 'voting';
	const UNANIMOUS_VOTE = 'u_voting';
	const OTHER = 'other';
	
	public function question() {
		return $this->belongsTo('Seimas\Question', 'questions_id', 'id');
	}
	
}