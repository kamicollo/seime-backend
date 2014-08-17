<?php

namespace Seimas;

class Question extends \Eloquent {
	protected $fillable = [];
	protected $table = 'questions';
	protected $primaryKey = 'id';
	public $timestamps = false;
	
	public function sitting() {
		return $this->belongsTo('Seimas\Sitting', 'sittings_id', 'id');
	}
	
	public function actions() {
		return $this->hasMany('Seimas\Action', 'questions_id', $this->primaryKey);
	}
}