<?php

namespace Seimas;

class Action extends \Eloquent {
	protected $fillable = [];
	protected $table = 'actions';
	protected $primaryKey = 'id';
	public $timestamps = false;
	
	public function question() {
		return $this->belongsTo('Seimas\Question', 'questions_id', 'id');
	}
}