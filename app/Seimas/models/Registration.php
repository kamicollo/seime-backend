<?php

namespace Seimas\models;


class Registration extends Action implements AncestorInterface {
	protected $registrationData;
	public $registration_data;
		
	public function members($presence = null) {
		return	
			$this->defaultPivotParameter(
				$this->belongsToMany('Seimas\models\Member', 'registrations', 'actions_id', 'members_id')
					->withPivot('presence'),
				'presence',
				$presence,
				'boolean'
			);
	}
	public function votes() {
		return $this->belongsToMany('Seimas\models\Vote', 'voting_registration', 'registration_id', 'voting_id');
	}
	
	public function setRegistrationData(\Generator $data) {
		$this->registrationData = $data;
	}
	
	public function getDescendantsData() {
		if ($this->registrationData !== null) {
			$array[] = new \Seimas\DescendantBag('RegistrationData', $this->registrationData);
		}
		return $array;
	}

	public function getDescendantFactory($type = null) {
		return function() {
			return $this;
		};
	}

	public function getDescendants() {
		return [];
	}
	
	public function save(array $options = array(), $recursively = false) {
		parent::save($options, $recursively);
		$this->saveRegistrationData();
	}
	
	public function saveRegistrationData() {
		foreach($this->registration_data as $member) {
			$this->members()->save(
				$member,
				['presence' => $this->registration_data->offsetGet($member)]
			);
		}
	}
	
	public function __sleep() {
		$this->registrationData = null;
		return array_merge(parent::__sleep(), ['registration_data']);
	}
}

