<?php

namespace Seimas;

class Session extends \Eloquent implements ParentInterface {
	use ParentTrait;
	
	protected $fillable = [];
	protected $table = 'sessions';
	protected $primaryKey = 'id';
	public $timestamps = false;
	
	public function sittings() {
		return $this->hasMany('Seimas\Sitting', 'sessions_id', $this->primaryKey);
	}
	
	public function loadChildren() {
		$this->children = $this->sittings()->orderBy('end_time', 'ASC')->get();
	}
}