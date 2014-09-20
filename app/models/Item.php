<?php

namespace Seimas;

class Item extends \Eloquent {
	protected $fillable = [];
	protected $table = 'items';
	protected $primaryKey = 'id';
	public $timestamps = false;
	
	public function question() {
		return $this->belongsTo('Seimas\Question', 'questions_id', $this->primaryKey);
	}
	
	public function presenters() {
		return $this->hasMany('Seimas\Presenter', 'items_id', $this->primaryKey);
	}
	
}