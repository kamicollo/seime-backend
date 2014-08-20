<?php
	
	class SittingStats extends Sitting {
	
		protected $topquestions_sql = '
			SELECT questions.id, AVG(subquestions_participation.presence) as e_presence 
			FROM questions
			JOIN subquestions ON questions.id = subquestions.questions_id
			JOIN subquestions_participation ON subquestions.id = subquestions_participation.subquestions_id
			WHERE questions.sittings_id = ?
			GROUP BY questions.id
			ORDER BY AVG(subquestions_participation.presence) * TIMEDIFF(questions.end_time,questions.start_time) DESC
			LIMIT 0, {limit}';
			
		protected $percentage_participation_sql = '
			SELECT ROUND(SUM(hours_present) / SUM(hours_available) * 100,0) 
			FROM `participation_data`
			WHERE sittings_id = ?';
			
		protected $full_attendance_sql = '
			SELECT COUNT(id) 
			FROM `participation_data`
			WHERE sittings_id = ? AND hours_present = hours_available';
			
		protected $short_attendance_sql = '
			SELECT COUNT(id) 
			FROM `participation_data`
			WHERE sittings_id = ? AND hours_present < hours_available * 0.3 AND hours_present > 0';
			
		protected $voting_results_sql = '
			SELECT outcome, count(actions.id) as count
			FROM questions
			JOIN actions ON actions.questions_id = questions.id
			WHERE questions.sittings_id = ? AND actions.type = "voting"
			GROUP BY outcome';
	
		public function __construct($url, Seimas $parent = NULL, $params = NULL, Factory $Factory = NULL) {
			parent::__construct($url, $parent, $params, $Factory);
			$this->initialise();
			$this->initialiseChildren(true);
		}
		
		public function getSpeakers($limit = 0) {
			if (empty($this->speakers)) {
				$members = array();
					foreach ($this->getChildren() as $child) {
						//echo $child->getTitle() . "<br>";
						foreach ($child->getChildren() as $action) {
							if ($action->getType() == 'speech') {
								$member = trim(mb_substr($action->getTitle(), 9));
								$length = strtotime($action->getEndTime()) - strtotime($action->getStartTime());								
								(!array_key_exists($member, $members)) ? $members[$member] = $length : $members[$member] += $length;
							}
						}
					}
				arsort($members);
				$this->speakers = $members;
			}
			return ($limit == 0) ? $this->speakers : array_slice($this->speakers, 0, $limit);
		}
		
		/*
		public function getTopQuestions($limit = 3) {
			$questions = array();
			$lengths = array();
			foreach ($this->getChildren() as $child) {
				$questions[] = $child;
				$lengths[] = strtotime($child->getEndTime()) - strtotime($child->getStartTime());
			}
			arsort($lengths);
			foreach($lengths as $key => &$v) {
				$v = $questions[$key];
			}
			return array_slice($lengths, 0, $limit);
		} */
		
		public function getTopQuestions($limit = 3) {
			$sql = str_replace('{limit}', intval($limit), $this->topquestions_sql);
			$db = Initialisator::getDB();
			$result = $db->getArray($sql,array($this->getId()));
			$top = array();
			foreach($result as $row) {
				$q = $this->children[$row['id']];
				$q->effective_presence = $row['e_presence'];
				$top[] = $q; 
			}
			return $top;
		}
		
		public function getTitle() {
			return implode(
							" ",
			 				array(
			 					strftime('%Y m. %B %e d.', strtotime($this->date)),
			 					$this->type,'posėdis')
			 				);
		}
		
		public function getPeriod() {
			return date('H:i', strtotime($this->getStartTime())) . ' - ' . date('H:i', strtotime($this->getEndTime()));
		}	
		
		public function getLength() {
			$l = (strtotime($this->getEndTime()) - strtotime($this->getStartTime())) / 60;
			$h = floor($l / 60);
			$m = $l % 60;
			return sprintf('%1$s val. %2$s min', $h, $m);
		}	
		
		public function getStartTime() {
			if (empty($this->start_time)) return $this->start_time;
			else {
				reset($this->children);
				$id = key($this->children);
				return $this->children["$id"]->getStartTime();
			}
		}
		
		public function getSessionID() {
			return $this->sessions_id;
		}
		
		public function getEndTime() {
			return $this->end_time;
		}
		
		public function getUrl($type = '') {
			switch ($type) {
				case '': return $this->url;
				case 'protocol': return $this->protocol_url;
				case 'transcript': return $this->transcript_url;
				case 'recording': return $this->recording_url;
			}
		}
		
		public function participation($type) {
			switch ($type) {
				case 'participated': 
					return array_sum($this->participation);
				case 'total': 
					return count($this->participation);
				case 'percentage': 
					return round($this->participation('participated') / $this->participation('total') * 100, 0);
				case 'time-based': 
					return $this->Factory->getVar($this->percentage_participation_sql, array($this->getId()));
			}
		}
		
		public function getMemberStats($type) {
			switch($type) {
				case 'full-attendance':
					$c = $this->Factory->getVar($this->full_attendance_sql, array($this->getId()));
					return (empty($c)) ? 0 : $c;
				case 'short-attendance':
					$c = $this->Factory->getVar($this->short_attendance_sql, array($this->getId()));
					return (empty($c)) ? 0 : $c;
				case 'speakers':
					return count($this->getSpeakers());				
			}		
		}
		
		public function getVotings($outcome) {
			if (empty($this->votingOutcomes)) {
				$this->votingOutcomes = array();
				foreach($this->Factory->getArray($this->voting_results_sql, array($this->getId())) as $row) { $this->votingOutcomes[$row['outcome']] = $row['count']; }
			}
			switch($outcome) {
				case 'all':
				return array_sum($this->votingOutcomes);
				case 'accepted':
				return	$this->votingOutcomes['accepted'];
				case 'rejected':
				return	$this->votingOutcomes['rejected'];
			}
		}
		
		public function getTopParticipants() {
			$sql = '
				SELECT members.id, members.name FROM participation_data
				JOIN members ON members_id = members.id 
				WHERE cadency_end = "0000-00-00" AND sittings_id = ? AND hours_present = hours_available
				ORDER BY members.name ASC';
			$participants = array();
			$top = $this->Factory->getArray($sql, array($this->getId()));
			if (!empty($top)) {
				foreach ($top as $m) {
					$participants[] = '<a href="http://seime.lt/' . getMemberLink($m['id']) . '">' . $m['name'] . '</a>';
				}
			}
			return $participants;
		}
		
		public function getBottomParticipants() {
			$sql = '
				SELECT hours_present FROM participation_data
				JOIN members ON members_id = members.id
				WHERE cadency_end = "0000-00-00" AND sittings_id = ? AND hours_present > 0
				ORDER BY hours_present ASC LIMIT 4,1';
			$cutoff = $this->Factory->getVar($sql, array($this->getId()));
			$sql = '
				SELECT members.id, members.name, round(hours_present / hours_available * 100,0) as participation FROM participation_data
				JOIN members ON members_id = members.id 
				WHERE cadency_end = "0000-00-00" AND sittings_id = ? AND hours_present <= ? AND hours_present > 0
				ORDER BY hours_present ASC, members.name ASC';
			$participants = array();
			$bottom = $this->Factory->getArray($sql, array($this->getId(), $cutoff));
			if (!empty($bottom)) {
				foreach ($bottom as $m) {
					$participants[] = '<a href="http://seime.lt/' . getMemberLink($m['id']) . '">' . $m['name'] . '</a> (' . $m['participation']  . '%)';
				}
			}
			return $participants;
		}
		
		public function getTopSpeakers() {
			$sql = '
				SELECT members.id, members.name, members.notes
				FROM members
				WHERE name IN (?,?,?,?,?)
				ORDER BY FIELD(members.name, ?, ?, ?, ?, ?)';
			
			$speakers = $this->getSpeakers(5);
			$top = $this->Factory->getArray($sql, array_merge(array_keys($speakers),array_keys($speakers)));
			$members = array();
			foreach ($top as $member) {
				$members[] = '<a href="http://seime.lt/' . getMemberLink($member['id']) . '">' . $member['name'] . '</a> (' . round($speakers[$member['name']] / 60, 0) . ' min)'; 
			}
			return $members;
		}
		
		public function TotalVotePie() {
			$total_data = $this->Factory->getArray('
				SELECT vote, count(vote) as count FROM votes
				JOIN actions ON actions.id = votes.actions_id
				JOIN questions ON actions.questions_id = questions.id
				WHERE vote != ? AND sittings_id = ? GROUP BY vote', array('not presen', $this->getId()));
			$totals = array();
			$total_count = 0;
			foreach ($total_data as $outcome) {
				$total_count += $outcome['count'];
				$totals[$outcome['vote']] = $outcome['count'];
			}
			$js_totals = array();
			foreach($totals as $name => $count) {
				$data = array('name' => niceVoteName($name), 'y' => $count / $total_count * 100);
				if ($name == 'disappeare') {
					$data['sliced'] = 1;
					$data['selected'] = 1;
				}
				$js_totals[] = $data;
			}
			usort($js_totals, array($this, 'sortPieChart'));
			return $js_totals;
	}
	
	protected function sortPieChart($a, $b) {
		if ($this->getPieOrder($a['name']) > $this->getPieOrder($b['name'])) return 1;
		elseif ($this->getPieOrder($a['name']) < $this->getPieOrder($b['name'])) return -1;
		else return 0;
	}

	protected function getPieOrder($name) {
		$v = 1;
		switch ($name) {
			case 'Balsavo UŽ': $v = 2; break;
			case 'Balsavo PRIEŠ': $v = 3; break;
			case 'Susilaikė': $v = 4; break;
			case 'Neužsiregistravo': $v = 1; break;
			case 'Užsiregistravo, tačiau nebalsavo': $v = 5; break;
		}
		return $v;
	}
		
	}
