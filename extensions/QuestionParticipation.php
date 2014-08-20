<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class QuestionParticipation extends Question {
	
	private static $subquestions_sql = 'SELECT * from subquestions WHERE questions_id = ? ORDER BY number ASC';
	private static $subquestions_participation_sql = 'SELECT * FROM subquestions_participation WHERE subquestions_id = ? ORDER BY members_id ASC';
	
	/* special data for manipulation */
	private $participation = array();
	
	/* EXTENDED FUNCTIONS FROM PARENT */
		
	
	public function __toString() {
		$array = get_object_vars($this);
		unset($array['PDO']);
		unset($array['Factory']);
		unset($array['parent']);
		unset($array['url_token']);	
		//unset($array['children']);
		if (is_array($array['children'])) {
			unset($array['children']['additional_data']);
			$array['children'] = $this->cleanChildren($array['children']);
		}
		return $array;
	}
			
	
	/*
	 * Wrapper function to be called from outside
	 */
	public function estimateParticipation() {
		//try to get the participation from registration actions
		$participation = $this->extractParticipation();
		//if there were no registrations, call upon estimation based on siblings
		if (false === $participation) {
			$participation = $this->estimateParticipationBySiblings();
		}
		$this->participation = $participation;		
		//$this->show();
	}
	
	/*
	 * tries to estimate participation based on its own children
	 * returns participation array or false, if not successful
	 */
	protected function extractParticipation() {
		$start_time = $this->getStartTime();
		$participation = array();
		$i = 0;
		$last_position = false;
		//collect all registrations into array $participation
		foreach($this->children as $index => $child) {
			if ($child->getType() == 'registration') {				
				$data = array(
					'participation' => $child->getParticipation(),
					'start_time' => $start_time,
					'end_time' => date('Y-m-d', strtotime($start_time)) . ' ' . $child->getEndTime(), //end time in action is only time!
					'number' => $i);
				$participation[$i] = $data;
				$i++;
				$start_time = $data['end_time'];				
				$last_position = $index;
			}
		}
		//if no registrations found - return false
		if (false === $last_position) {
			return false;
		}
		//else - extend the last registration effect till the end of question
		else {
			$participation[$i - 1]['end_time'] = $this->getEndTime();
			return $participation;
		}		
	}
	
	/*
	 * tries to estimate participation based on sibling / parent data
	 */
	protected function estimateParticipationBySiblings() {		
				
		$participation = array();
		$preceeding_participation = false;
		$proceeding_participation = false;
		
		//first, try to get the question before with data		
		$a = $this->getSiblingParticipation(-1);
		if (false !== $a) {
			$a = end($a);
			$preceeding_participation = $a['participation'];
		}
		//then, try to get the question after with data		
		$a = $this->getSiblingParticipation(1);
		if (false !== $a) {
			$a = reset($a);
			$proceeding_participation = $a['participation'];
		}		
				
		if ((false === $preceeding_participation) && (false === $proceeding_participation)) {
		//case when no questions had registrations - return Sitting participation data
			$participation = $this->getParentInfo('getParticipation');			
			echo 'OMFG! it exists!';
			echo $this->getId();
		}
		elseif (false === $proceeding_participation) {
		//if no data going forward, assume same participation as in question before
			$participation = $preceeding_participation;			
		}
		elseif (false === $preceeding_participation) {
		//if no data going back, assume same participation as in question afterwards						
			$participation = $proceeding_participation;
		}
		else {
			//merge data from siblings. If member present in at least one of siblings - assume presence here, too.
			foreach ($preceeding_participation as $member => $presence) {
				$participation[$member] = $presence;
			}
			foreach ($proceeding_participation as $member => $presence) {
				if ($presence) {
					$participation[$member] = 1;
				}
				elseif (!isset($participation[$member])) {
					$participation[$member] = 0;
				}
			}			
		}
		
		$data = array(
				'participation' => $participation,				
				'start_time' => $this->getStartTime(),
				'end_time' => $this->getEndTime(),
				'number' => 0);			
		return array($data);
	}
	
	/*
	 * helper function for Question::estimateParticipationBySiblings()
	 */	
	protected function getSiblingParticipation($direction)	{
		$i = 1;
		$found = false;
		while(!$found) {
			try {
				$sibling_participation = $this->getSiblingInfoByPosition($this->getId(), $i * $direction, 'getParticipation');
				if (false !== $sibling_participation) {
					//if found, return
					return $sibling_participation;
				}
				else {
					//else, keep searching
					$i++;
				}
			}
			catch(Exception $e) {
				return false; //no such sibling found at all
			}
		}
	}
		
	/*
	 * public function, returns participation if already estimated before
	 * OR
	 * tries to estimate based on internal data only (calls extractParticipation)
	 * else returns false;
	 */
	public function getParticipation() {
		if (!empty($this->participation)) {
			//provide data, if available
			return $this->participation;
		}
		else {
			//else try to extract, but do not go into siblings to avoid recursion
			$participation = $this->extractParticipation();
			if (false === $participation) {
			//if unlucky, return false
				return false;
			}
			else {
			//else save it properly and return it
				$this->participation = $participation;
				return $participation;
			}			
		}
	}

	public function saveParticipation() {
		if (empty($this->participation)) return;
		$id = NULL;
		
		foreach($this->participation as $subquestion) {
			$participation_data = $subquestion['participation'];
			$data = array();
			unset($subquestion['participation']);
			
		//saving of subquestion meta data
			if (!isset($subquestion['id'])) {
				$subquestion['questions_id'] = $this->getId();
				$id = $this->Factory->saveObject('subquestions', $subquestion, array('id', 'questions_id'));
			}
			else $id = $subquestion['id'];
		
		//saving of participation data
			if (!empty($id)) {
				foreach($participation_data as $member => $presence) {
					$data[] = array('subquestions_id' => $id, 'members_id' => $member, 'presence' => $presence);
				}
				$this->Factory->saveObjects('subquestions_participation', $data, array('id'));
			}
			else {
				echo 'error on saving - where is the ID?';
			}
		}				
	}
		
	public function populateParticipation() {
		
		$subquestions = $this->Factory->getArray(self::$subquestions_sql, array($this->getId()));		
		if (empty($subquestions)) {
			return false;
		}
		else {
			foreach ($subquestions as $subquestion) {
				$data = $subquestion;				
				$participation = $this->Factory->getArray(self::$subquestions_participation_sql, array($subquestion['id']));
				if (!empty($participation)) {
					$data['participation'] = array();
					foreach ($participation as $row) {
						$data['participation'][$row['members_id']] = $row['presence'];
					}
				}
				$this->participation[$data['number']] = $data;				
			}
			return true;
		}		
	}
	
}
?>
