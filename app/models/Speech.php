<?php

namespace Seimas;


class Speech extends Action {
		
	public function member() {
		return $this->belongsToMany('Seimas\Member', 'speakers', 'actions_id', 'members_id')->first();
	}
}

