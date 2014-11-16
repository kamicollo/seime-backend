<?php

namespace Seimas\models;


class Speech extends Action {
	
	public $speaker = null;
		
	public function member() {
		return $this->members()->first();
	}
	
	public function members() {
		return $this->belongsToMany('Seimas\models\Member', 'speakers', 'actions_id', 'members_id');
	}
	
	public function save(array $options = array(), $recursively = false) {
		parent::save($options, $recursively);
		$this->saveSpeakers();
	}
	
	public function saveSpeakers() {
		if ($this->speaker !== null) {
			$this->members()->save($this->speaker);
		}
	}
	
	public function __sleep() {
		return array_merge(parent::__sleep(), ['speaker']);
	}
}

