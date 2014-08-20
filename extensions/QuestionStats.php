<?php

	class QuestionStats extends Question {
	
	public static $presenters_sql = 'SELECT presenters.*, members.id as members_id FROM `presenters` LEFT JOIN members ON presenters.presenter = members.name WHERE items_id = ? ORDER BY number ASC';
	
		public function getPresenters() {
			$presenters = array();
			if ((isset($this->items[0])) && (!empty($this->items[0]['presenters']))) {
				foreach($this->items[0]['presenters'] as $p) {
					$presenters[$p['presenter']] = $p['members_id'];
				}
			return $presenters;
			}
		}
		
		public function getSpeakers() {
			$members = array();			
			foreach ($this->getChildren() as $action) {
				if ($action->getType() == 'speech') {
					$member = trim(mb_substr($action->getTitle(), 9));
					$length = strtotime($action->getEndTime()) - strtotime($action->getStartTime());								
					(!array_key_exists($member, $members)) ? $members[$member] = $length : $members[$member] += $length;
				}
			}
			return $members;
		}
		
		public function getLastVoting() {
			$voting = null;
			foreach ($this->getChildren() as $action) {
				if ($action->getType() == 'voting') {
					$voting = $action;
				}
			}
			return $voting;
		}
		
	}
