<?php

/**
 * Description of Action
 *
 * @author aurimas
 */
class Action extends HTMLObject {

	public static $url_token = '';
	public static $create_sql = '';
	public static $speaker_sql = 'SELECT * FROM `speakers` WHERE actions_id = ?';
	public static $registrations_sql = 'SELECT * FROM `registrations` WHERE actions_id = ?';
	public static $votes_sql = 'SELECT * FROM `votes` WHERE actions_id = ?';
		
	/* variables to be saved */
	protected $dom;
	protected $type = 'other';
	protected $end_time;
	protected $start_time;
	protected $title;
	protected $number;
	protected $questions_id;
	
	/* possibly empty variables */	
	protected $total_participants = 0;
	protected $outcome = '';
	protected $voting_topic = '';
	
	
	/* additional data - saved separately based on action type */
	protected $additional_data = array();
	

	public function __construct($url, Seimas $parent = NULL, $params = NULL, Factory $Factory = NULL) {		
		parent::__construct($url, $parent, $params, $Factory);
		if (!$this->PDO) {
			if ($params['dom'] instanceof DOMElement) {
				$this->dom = $params['dom'];				
			}
			else {
				throw new Exception('No valid DOM Element provided to the Action Object');
			}
			$this->number = $params['id'];			
			$this->url = '';
			$this->questions_id = $this->getParentInfo('getId');
		}
		else {
			$this->dom = $this->unserialise($this->dom);			
		}
	}
	
	protected function saveData() {		
		$this->saveMainData();		
		if (!empty($this->additional_data)) {			
			$this->saveAdditionalData();
		}
	}
	
	public function saveMainData() {
		$array = get_object_vars($this);
		unset($array['PDO']);
		unset($array['Factory']);
		unset($array['parent']);
		unset($array['items']);					
		unset($array['children']);		
		unset($array['additional_data']);		
		$array['dom'] = $this->serialise($this->dom);				
		$id = $this->Factory->SaveObject('actions', $array, array('id', 'questions_id'));
		if ($id != 0) {
			$this->id = $id;
		}
		else {
			$this->id = $this->Factory->getVar('SELECT id FROM actions WHERE questions_id = ? and number = ?', array($this->questions_id, $this->number));
			if ($this->id == 0) echo 'blah!';
		}
	}
	
	protected function saveAdditionalData() {
		if (empty($this->id)) {
			$this->show();
			return;
		}
			
		
		switch($this->type) {
		
		/* speaker saving */
			case 'speech':								
				if (isset($this->additional_data['speaker'])) {					
					$data = array('members_id' => $this->additional_data['speaker'], 'actions_id' => $this->getId());
					$this->Factory->saveObject('speakers', $data, array('actions_id'));
				}								
			break;
		
		/* registration data saving */	
			case 'registration':				
				$data = array();
				foreach ($this->additional_data['participation'] as $members_id => $presence) {
					$data[] = array('actions_id' => $this->getId(), 'members_id' => $members_id, 'presence' => $presence);
				}
				if (!empty($data)) {
					$this->Factory->saveObjects('registrations', $data, array('members_id', 'id', 'actions_id'));
				}
			break;
		/* voting data saving */
			case 'voting':				
				$data = array();
				foreach ($this->additional_data['voting'] as $vote) {
					$data[] = array(
						'actions_id' => $this->getId(),
						'members_id' => $vote['id'],
						'fraction' => $vote['fraction'],
						'vote' => $vote['vote']);					
				}
				if (!empty($data)) {
					$this->Factory->saveObjects('votes', $data, array('members_id', 'id', 'actions_id'));
				}
			break;						
		}
	}
	
	protected function populateData() {
		if ($this->PDO) {
			$this->populateAdditionalData();
			if (!in_array($this->type, array('voting', 'registration'))) {
				//everything should be here, only load and save additional data								
				return true;			
			}
			elseif (empty($this->additional_data)) {
				echo 'here!';
				//no data present for registrations and voting - initial DB run
				return false;
			}
			else {
				//all data loaded - we are good to go
				return true;
			}							
		}
		else {
			//not loaded via DB - need to parse additional data		
			return false;
		}
	}
	
	protected function populateAdditionalData() {			
		
		switch($this->type) {
		
		/* populate speaker data */		
			case 'speech':				
				$speakers = $this->Factory->getArray(self::$speaker_sql, array($this->getId()));
				if (is_array($speakers)) {
					foreach ($speakers as $speaker) {
						$this->additional_data['speaker'] = $speaker['members_id'];
					}
				}
			break;
		
		/* populate registration data */
			case 'registration':				
				$registrations = $this->Factory->getArray(self::$registrations_sql, array($this->getId()));
				if (is_array($registrations)) {
					$this->additional_data['participation'] = array();
					foreach ($registrations as $registered) {
						$this->additional_data['participation'][$registered['members_id']] = $registered['presence'];
					}
				}
			break;
		
		/* populate voting data */			
			case 'voting':				
				$votes = $this->Factory->getArray(self::$votes_sql, array($this->getId()));
				if (is_array($votes)) {
					$this->additional_data['votes'] = array();
					foreach ($votes as $vote) {
						$array = array('id' => $vote['members_id'], 'fraction' => $vote['fraction'], 'vote' => $vote['vote']);
						$this->additional_data['votes'][$array['id']] = $array;
					}
				}
			break;							
		}
	}
	
	public function initialiseChildren($recursive = false) {
		return;
	}
	
	protected function scrapeData($reload = FALSE) {
				
		/* parse additional urls */
		if (!empty($this->url)) {
			$function = "get" . ucfirst($this->type) . 'Data';
			$this->$function();
		}
	}
	
	public function parseData() {
		$tds = $this->dom->getElementsByTagName('td');
		if ($tds->length != 2) {
			log_f('parsing error: Action table td count', $this->getId());
		} 
		else {
			/* Set start time */
			$this->start_time = $this->clean($tds->item(0)->nodeValue);
			
			/* set end time */
			try {
				$this->end_time = $this->getSiblingInfoByPosition($this->number, +1, 'getStartTime');
			}
			catch(Exception $e) {
				$this->end_time = $this->start_time;
			}
						
			/* determine action type & additional data */
			
			$this->title = $this->decode($tds->item(1)->nodeValue);
			
			if (stripos($this->title, 'Kalbėjo') !== false) { //action type - speech
				$this->type = 'speech';
				$this->additional_data['speaker'] = $this->getMemberId($tds->item(1));
			}
						
			elseif (stripos($this->title, 'bendru sutarimu') !== false) { //action type - voting (together)
				$this->type = 'u_voting';				
				if (stripos($this->title, 'pritarta') !== false) {
					$this->outcome = 'accepted';
				} 
				else {
					$this->outcome = 'rejected';
				}
			}
			
			elseif (stripos($this->title, 'Įvyko registracija') !== false) { //action type - registration
				$this->type = 'registration';
				$matches = array();
				preg_match('/užsiregistravo.\s*(\d+)/u', $this->title, $matches);
				if (isset($matches[1])) {
					$this->total_participants = $matches[1];
				}

				$reg_link = $tds->item(1)->getElementsByTagName('a')->item(0);
				if (!is_object($reg_link)) {
					log_f('parsing error: question - registration link', $this->getId());
				}
				else {
					$link = self::BASE_URL . $reg_link->getAttribute('href');
					$this->url = $link;										
				}
			}			
			elseif (stripos($this->title, 'Įvyko balsavimas') !== false) { //action type voting
				$this->type = 'voting';
				
				/* general outcome of voting */
				if (stripos($this->title, 'pritarta') !== false) {
					$this->outcome = 'accepted';
				} 
				else {
					$this->outcome = 'rejected';
				}
				
				/* individual outcome of voting */
				$voting_link = $tds->item(1)->getElementsByTagName('a')->item(0);
				if (!is_object($voting_link)) {
					log_f('parsing error: question - voting link', $this->getId());
				}					
				else {
					$link = self::BASE_URL . $voting_link->getAttribute('href');
					$this->url = $link;										
				}
			}						
		}
	}
	
	protected function getRegistrationData() {
		$reg_dom = $this->getHTMLDOM($this->url);		
		$xpath = new DOMXPath($reg_dom);		
		$members = $xpath->query("//table[contains(@cellpadding, '1')]//table[contains(@width, '100%')]/tr");
		$this->additional_data['participation'] = array();
		foreach ($members as $member_data) {

			$tds = $member_data->getElementsByTagName('td');
			if ($tds->length != 2) {
				log_f('parsing error: action lankomumo lentele - participation . ', $this->getId());			
			}
			else {						
				$member_id = $this->getMemberId($tds->item(1));
				if ($this->clean($tds->item(0)->nodeValue) == '') $participation = 0;
				else $participation = 1;
				$this->additional_data['participation'][$member_id] = $participation;
			}				
		}
		unset($reg_dom);		
	}
	
	protected function getVotingData() {
		$voting_dom = $this->getHTMLDOM($this->url);
		
		//get voting topic
		$inner_html = $this->decode(DOMinnerHTML($voting_dom->getElementsByTagName('body')->item(0)));
		preg_match("/Formuluot.+?\s+<b>(.*?)<\/b>/msu", $inner_html, $matches);
		if (isset($matches[1])) {
			$this->voting_topic = $matches[1];
		}

		//get voting data
		$this->additional_data['voting'] = array();				
		$xpath = new DOMXPath($voting_dom);
		$voting_dom = $xpath->query("//table[contains(@class, 'basic')]/tr[td]");
		foreach ($voting_dom as $member_data) {
			$member = array();
			$td2 = '';
			$td3 = '';
			$td4 = '';
			$tds = $member_data->getElementsByTagName('td');
			if ($tds->length != 5) {
				log_f('parsing error: voting data', $this->getId());
			}
			else {
				$member['id'] = $this->getMemberId($tds->item(0));
				$member['fraction'] = $this->clean($tds->item(1)->nodeValue);				
				$member['vote'] = '';
				$td2 = $this->clean($tds->item(2)->nodeValue);
				$td3 = $this->clean($tds->item(3)->nodeValue);
				$td4 = $this->clean($tds->item(4)->nodeValue);
				if (!empty($td2)) {
					$member['vote'] = 'accept';
				}
				elseif (!empty($td3)) {
					$member['vote'] = 'reject';
				}
				elseif (!empty($td4)) {
					$member['vote'] = 'abstain';
				}
				else {
					$member['vote'] = 'not present';
				}
				$this->additional_data['voting'][$member['id']] = $member;
			}
		}
		unset($xpath);
	}
	
	public function getEndTime() {		
		if (empty($this->end_time)){
			$this->parseData();
		}
		if ($this->end_time == $this->start_time) {
			$this->end_time = date('H:i:s', strtotime($this->end_time) + 60);
			/* 
			It is possible to obtain the end time from next question, but there's an issue of breaks (not seen in statistics).
			Thus, for now we just assume that the last action took 60 seconds.
			try {
				$end = $this->parent->getSiblingInfoByPosition($this->parent->getId(), 1, 'getStartTime');
				$end_date = substr($end, 0, 10);
				if (strtotime($end) > strtotime($end_date . ' ' . $this->end_time)) $this->end_time = date('H:i:s', strtotime($end));
				else $this->end_time = date('H:i:s', strtotime($this->end_time) + 60);
			}
			catch(Exception $e) {	$this->end_time = date('H:i:s', strtotime($this->end_time) + 60); }
			*/
		}
		return $this->end_time;
	}
	
	public function getStartTime() {
		if (empty($this->start_time)){
			$this->parseData();
		}
		return $this->start_time;
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function getNumber() {
		return $this->number;
	}
	
	public function getTitle() {
		return $this->title;
	}
	
	public function getParticipation() {
		if (isset($this->additional_data['participation'])) {
			return $this->additional_data['participation'];
		}
		else {
			return false;
		}
	}
	
	public function getVoting($type = 'present') {
		switch($type) {
			case 'accepted': return $this->Factory->getVar('SELECT count(id) FROM votes WHERE actions_id = ? AND vote = ?', array($this->getId(), 'accept'));
			case 'rejected': return $this->Factory->getVar('SELECT count(id) FROM votes WHERE actions_id = ? AND vote = ?', array($this->getId(), 'reject'));
			case 'abstain': return $this->Factory->getVar('SELECT count(id) FROM votes WHERE actions_id = ? AND vote = ?', array($this->getId(), 'abstain'));
			case 'not present': return $this->Factory->getVar('SELECT count(id) FROM votes WHERE actions_id = ? AND vote = ?', array($this->getId(), 'not presen'));
			case 'present': return $this->Factory->getVar('SELECT count(id) FROM votes WHERE actions_id = ? AND vote != ?', array($this->getId(), 'not presen'));
		}
	}
	
	public function getVotingTopic() {
		return $this->voting_topic;
	}
	
	public function getVotingOutcome() {
		return $this->outcome;
	}
	
	protected function serialise(DOMElement $dom) {
		$newDom = new DOMDocument('1.0', 'UTF-8');		
		$root = $newDom->createElement('root');
		$root = $newDom->appendChild($root);
		$domNode = $newDom->importNode($dom, true);
		$root->appendChild($domNode);		
		return $newDom->saveXML($root);
	}
	
	protected function unserialise($dom) {
		$newDom = new DOMDocument('1.0', 'UTF-8');
		$newDom->loadXML($dom);
		return $newDom->getElementsByTagName('tr')->item(0);		
	}

}

?>
