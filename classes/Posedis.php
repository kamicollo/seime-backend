<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Posedis
 *
 * @author aurimas
 */
class Sitting extends HTMLObject {
	
	static public $create_sql = 'SELECT *, 1 as PDO FROM `sittings` WHERE id = ? ORDER BY id ASC';
	static public $children_sql = 'SELECT *, 1 as PDO FROM `questions` WHERE sittings_id = ? ORDER by id ASC';	
	static public $participation_sql = 'SELECT `members_id`, `presence` FROM sitting_participation WHERE sittings_id = ? ORDER by id ASC';
	static public $child_class = 'question';
	static public $url_token = 'p_fakt_pos_id=-';
	
	/* temporary */	
	protected $date;
		
	/* to be saved to DB */
	protected $number;
	protected $type;		
	protected $transcript_url = '';
	protected $recording_url = '';
	protected $protocol_url = '';	
	protected $participation_url = '';
	protected $sessions_id;
	
	/* special data - to be saved separately */
	protected $participation = array();
		
	protected function populateData() { 
		if ($this->PDO) {
			//loaded via DB			
			if (empty($this->number) || ($this->end_time == '0000-00-00 00:00:00')) {
				//initial run - let's scrape additional data
				return false;
			}
			else {
				//all data from DB present - let's only add children, participation & date
				$this->populateChildren();
				$this->populateParticipation();
				$this->date = date('Y-m-d', strtotime($this->end_time));				
				return true;
			}			
		}
		else {
			$this->sessions_id = $this->getParentInfo('getId');
			return false; //not loaded via DB - scrape everything
		}		
	}
	
	protected function populateParticipation() {
		$participation = $this->Factory->getArray(self::$participation_sql, array($this->getId()));
		foreach ($participation as $pair) {
			$this->participation[$pair['members_id']] = $pair['presence'];
		}		
	}

	protected function saveData() {
	//added check if the data is correct (e.g. end-time = 0000-00-00 00:00:00
		if ($this->end_time == '0000-00-00 00:00:00') {
			$this->clearCache($this->transcript_url);
			$this->clearCache($this->recording_url);
			$this->clearCache($this->protocol_url);
			$this->clearCache($this->participation_url);
			$this->clearCache($this->url);						
			return;
		}
		else {
			$array = get_object_vars($this);
			unset($array['PDO']);
			unset($array['Factory']);
			unset($array['parent']);
			unset($array['date']);
		
			/* parse children data */
			$children_array = array();
			foreach ($array['children'] as $child) {
				$children_array[] = array('id' => $child->getId(), 'url' => $child->getUrl(), 'sittings_id' => $this->getId());
			}
			unset($array['children']);
		
			/* parse parcitipation data */
			$participation_array = array();
			foreach ($array['participation'] as $member_id => $presence) {
				$participation_array[] = array('members_id' => $member_id, 'presence' => $presence, 'sittings_id' => $this->getId());
			}
			unset($array['participation']);		
			$this->Factory->SaveObject('sittings', $array, array('id'));
			$this->Factory->SaveObjects('questions', $children_array, array('sittings_id', 'id'));
		
			$this->Factory->SaveObjects('sitting_participation', $participation_array, array());
		}
	}
	
	protected function scrapeData($reload = FALSE) {
		$dom = $this->getHTMLDOM($this->url, $reload);
		$this->getMetaData($dom);
		$this->extractParticipation($dom);
		$this->extractQuestions($dom);		
	}
	
	protected function getMetaData($dom) {
		
		/* title parsing */
		$title = $dom->getElementsByTagName('title')->item(0)->nodeValue;		
		$matches = array();
		preg_match("/Seimo posėdis\s+Nr\.(\d+)\s+\((\d{4}-\d{2}-\d{2}), (.+)\)/u", $title, $matches);
		if (count($matches) < 4) {
			throw new Exception('Something wrong with Sitting parsing @' . $this->url);
		}
		$this->number = trim($matches[1]);
		$this->date = trim($matches[2]);
		$this->type = trim($matches[3]);
		
		/* finding links to content */
		$xpath = new DOMXPath($dom);		
		$a_dom = $xpath->query("//a[.='Protokolas']")->item(0);		
		if (is_object($a_dom)) $this->protocol_url = $a_dom->getAttribute('href');
		$a_dom = $xpath->query("//a[.='Stenograma']")->item(0);		
		if (is_object($a_dom)) $this->transcript_url = $a_dom->getAttribute('href');
		$a_dom = $xpath->query("//a[.='Garso įrašas']")->item(0);		
		if (is_object($a_dom)) $this->recording_url = $a_dom->getAttribute('href');
		unset($xpath);
				
	}
	
	protected function extractParticipation($dom) {
		$xpath = new DOMXPath($dom);	
		$a_dom = $xpath->query("//a[.='Lankomumas']")->item(0);		
		if (is_object($a_dom)) $this->participation_url = self::BASE_URL . $a_dom->getAttribute('href');
		unset($xpath);
		if (!empty($this->participation_url)) {
			$lankomumas_dom = $this->getHTMLDOM($this->participation_url);
			$xpath = new DOMXPath($lankomumas_dom);			
			$nariai_dom = $xpath->query("//table[contains(@cellpadding, '1')]//table[contains(@width, '100%')]/tr");
			foreach ($nariai_dom as $nario_data) {
				
				$tds = $nario_data->getElementsByTagName('td');
				$participation = false;
				$person_id = false;
				
				$state = $tds->item(0);
				if (is_object($state)) {
					$value = $this->clean($state->nodeValue);
					if (empty($value)) $participation = 0;
					else $participation = 1;
				}				
				else log_f('parsing error: lankomumo lentele - participation . ', $this->getId ());								
				$person_id = $this->getMemberId($tds->item(1));
												
				if (($participation !== false) && ($person_id !== false)) {
					$this->participation[$person_id] = $participation;
				}								
			}
			unset($lankomumas_dom);
		}	
	}
	
	protected function extractQuestions($dom) {
		$xpath = new DOMXPath($dom);	
		$table_dom = $xpath->query("//table[contains(@class,'basic')]/tr[td]");
		$i = 0;
		$klausimas = false;
		foreach ($table_dom as $row) {
			$data = array();
			$url = '';
			$klausimas = false;
			$tds = $row->getElementsbyTagName('td');			
			if ($tds->length < 3) log_f('parsing error: darbotvarkes lentele', $this->getId());
			else {
				$data['start_time'] = $this->getDate() . ' ' . $this->clean($tds->item(0)->nodeValue);				
				//$data['number'] = $this->clean($tds->item(1)->nodeValue); replaced with actual number in the list
				$data['number'] = $i;
				$data['title'] = $this->clean($tds->item(2)->nodeValue);
				$link_dom = $tds->item(2)->getElementsByTagName('a');
				if (is_object($link_dom->item(0))) {
					$url = self::BASE_URL . $link_dom->item(0)->getAttribute('href');					
				}
				else log_f('parsing error: darbotvarkes lentele - klausimas a', $this->getId());
				
				if (!empty($url)) {
					$klausimas = $this->Factory->getObject(self::$child_class, $url, '', $this, $data);					
					$this->children[$klausimas->getId()] = $klausimas;					
					if ($i++ == 0) $this->start_time = $this->date . ' ' .$klausimas->getStartTime();
				}								
			}
		}
		if (is_object($klausimas)) $this->end_time = $klausimas->getEndTime();
		unset($xpath);
		unset($table_dom);
	}
	
	public function getDate() {
		return $this->date;
	}
	
	public function getParticipation() {
		return $this->participation;
	}
}

?>
