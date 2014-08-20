<?php

namespace Seimas;

class Subquestion extends \Eloquent {
	use DefaultParameterTrait;
	
	protected $fillable = [];
	protected $table = 'subquestions';
	protected $primaryKey = 'id';
	public $timestamps = false;

	
	public function question() {
		return $this->belongsTo('Seimas\Question', 'questions_id', $this->primaryKey);
	}
	
	public function members($participated = null) {
		return	
			$this->defaultPivotParameter(
				$this->belongsToMany('Seimas\Member', 'subquestions_participation', 'subquestions_id', 'members_id')
					->withPivot('presence'),
				'presence',
				$participated,
				'boolean'
			);
	}
	
}