<?php

namespace Seimas;

class Question extends \Eloquent {
	protected $fillable = [];
	protected $table = 'questions';
	protected $primaryKey = 'id';
	public $timestamps = false;
	
	public function sitting() {
		return $this->belongsTo('Seimas\Sitting', 'sittings_id', $this->primaryKey);
	}
	
	public function actions() {
		return $this->hasMany('Seimas\Action', 'questions_id', $this->primaryKey);
	}
	
	public function registrations() {
		return $this->hasMany('Seimas\Registration', 'questions_id', $this->primaryKey)
				->where('type', Action::REGISTRATION);
	}
	
	public function votes() {
		return $this->hasMany('Seimas\Vote', 'questions_id', $this->primaryKey)
				->where('type', Action::VOTE);
	}
	
	public function speeches() {
		return $this->hasMany('Seimas\Speech', 'questions_id', $this->primaryKey)
				->where('type', Action::SPEECH);
	}
	
	public function unanimousVotes() {
		return $this->hasMany('Seimas\Vote', 'questions_id', $this->primaryKey)
				->where('type', Action::UNANIMOUS_VOTE);
	}
}