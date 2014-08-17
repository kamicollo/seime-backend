<?php

namespace Seimas;

class Session extends \Eloquent {
	protected $fillable = [];
	protected $table = 'sessions';
	protected $primaryKey = 'id';
	public $timestamps = false;
	
	public function sittings() {
		return $this->hasMany('Seimas\Sitting', 'sessions_id', $this->primaryKey);
	}
}