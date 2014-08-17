<?php

namespace Seimas;

class Member extends \Eloquent {
	protected $fillable = [];
	protected $table = 'members';
	protected $primaryKey = 'id';
	public $timestamps = false;
	
	public function sittings() {
		return $this->belongsToMany('Seimas\Sitting', 'sitting_participation', 'members_id', 'sittings_id')
				->withPivot('presence');
	}
	
}