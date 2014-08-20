<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Question extends HTMLObject {

	public static $url_token = 'p_svarst_kl_stad_id=-';
	public static $create_sql = 'SELECT *, 1 as PDO FROM questions WHERE id = ? ORDER by id ASC';
	public static $child_class = 'action';
	public static $children_sql = 'SELECT *, 1 as PDO FROM actions WHERE questions_id = ? ORDER BY id ASC';
	public static $items_sql = 'SELECT * FROM `items` WHERE questions_id = ? ORDER BY number ASC';
	public static $presenters_sql = 'SELECT * FROM `presenters` WHERE items_id = ? ORDER BY number ASC';
				
	/* helper variable - not to be saved */
	protected $date;
	
	protected $start_time;
	protected $end_time;
	protected $title;
	protected $number;
	protected $sittings_id;
	
	/* special data to be saved separately */
	protected $items = array();
	
	/* temp for debugging */
	public function __toString() {
		$array = parent::__toString();
		unset($array['actions']);
		return $array;
	}
	
	public function __construct($url, Seimas $parent = NULL, $params = NULL, Factory $Factory = NULL) {		
		parent::__construct($url, $parent, $params, $Factory);
		if (!$this->PDO) { //if object created not via DB
			$this->start_time = $params['start_time'];		
			$this->title = $this->decode($this->clean($params['title']));
			$this->number = $params['number'];
			$this->sittings_id = $this->getParentInfo('getId');			
		}
		$this->date = date('Y-m-d', strtotime($this->start_time));
	}
	
	protected function populateData() {
		if ($this->PDO) {
			//loaded via DB
			if (empty($this->end_time)) { 
				//initial run - let's scrape additional data
				return false;
			}
			else {
				//all data loaded, only populate children / etc
				$this->PopulateChildren();
				$this->PopulateItems();
				return true;
			}
		}
		else {
			//not loaded via DB - scrape all data			
			return false;			
		}
	}
	
	protected function populateItems() {
		$items = $this->Factory->getArray(self::$items_sql, array($this->getId()));
		foreach ($items as $item) {			
			$presenters = $this->Factory->getArray(self::$presenters_sql, array($item['id']));
			if (false != $presenters) {
				$item['presenters'] = $presenters;
			}
			else {
				$item['presenters'] = array();
			}
			$this->items[$item['number']] = $item;			
		}
	}

	protected function saveData() {
		$array = get_object_vars($this);
		unset($array['PDO']);
		unset($array['Factory']);
		unset($array['parent']);
		unset($array['items']);					
		unset($array['children']);		
		unset($array['date']);
				
		$this->Factory->SaveObject('questions', $array, array('id'));
		$this->saveItems();
		$this->saveChildren();				
	}
	
	protected function saveItems() {
		foreach ($this->items as $number => $item) {
			$presenters = $item['presenters'];
			unset($item['presenters']);
			$item['questions_id'] = $this->getId();
			$item['number'] = $number;			
			$item_id = 0;
			$item_id = $this->Factory->saveObject('items', $item, array('id', 'questions_id'));
			if (0 == $item_id) {
				if (isset($item['id'])) { //some random anomaly of some items being here twice...
					$item_id = $item['id']; // if DB returns 0, the item was in DB before, thus ID attrib. should be present in array
				}
			}
			/* save presenters data */
			if (0 != $item_id ) {
				foreach ($presenters as $number => $presenter) {
					$this->Factory->saveObject('presenters', array('presenter' => $presenter, 'items_id' => $item_id, 'number' => $number), array('id', 'items_id'));
				}			
			}
		}				
	}
	
	protected function saveChildren() {
		
	}

	protected function scrapeData() {
		$dom = $this->getHTMLDOM($this->url);
		$this->getItems($dom);
		echo 'here!';
		$this->getActions($dom);
	}

	protected function getItems(DOMDocument $dom) {
		$xpath = new DOMXPath($dom);
		$questions_dom = $xpath->query("//li[preceding::h4 and following::h4]");
		if ($questions_dom->length > 0) { //if more than one inner question
			$i = 0;
			foreach ($questions_dom as $question_dom) {
				//get data for each inner question
				$this->items[$i++] = $this->getItemMetaData($question_dom);
			}
		} 
		else {
			$questions_dom = $xpath->query("//node()[preceding::h4 and following::h4]");
			if ($questions_dom->length > 0) { //if one question only, need to create a new DOMElement (shame on you, XPATH!)
				$newDom = new DOMDocument('1.0', 'UTF-8');
				$root = $newDom->createElement('root');
				$root = $newDom->appendChild($root);
				$prev = '';
				foreach ($questions_dom as $question_dom) {
					if ($question_dom->nodeValue != $prev) {
						$domNode = $newDom->importNode($question_dom, true);
						$root->appendChild($domNode);
					}
					$prev = $question_dom->nodeValue;
				}
				$this->items[0] = $this->getItemMetaData($root);
			}
			else
				log_f('klausimo lentele: metadata not found', $this->getId());
		}
		unset($xpath);
	}

	protected function getItemMetaData(DOMElement $dom) {

		$data = array();
		
		//find document links
		$links = $dom->getElementsByTagName('a');
		foreach ($links as $link) {			
			$db_field = $this->getLinkType($this->decode(str_replace(array(chr(160), chr(194)), ' ', $link->nodeValue)));
			$data[$db_field] = $link->getAttribute('href');
		}
		
		//find title of question
		$title = $dom->getElementsByTagName('b')->item(0);
		if (is_object($title))
			$data['title'] = $this->decode($title->nodeValue);

		//find speakers
		$decoded = $this->decode(DOMinnerHTML($dom));
		$data['presenters'] = array();
		$pos = stripos($decoded, 'Pranešėja');
		if ($pos !== false) {
			preg_match_all('/<b>(.*?)<\/b>/u', substr($decoded, $pos + 9), $matches, PREG_SET_ORDER);
			foreach ($matches as $match) {
				if (isset($match[1]))
					$data['presenters'][] = $match[1];
			}
		}
		return $data;
	}
	
	protected function getLinkType($lithuanian_string) {		
		switch($lithuanian_string) {
			case 'dokumento tekstas': return 'document_url';
			case 'susiję dokumentai': return 'related_doc_url';
			default: return 'other_url';
		}
	}

	protected function getActions($dom) {

		$xpath = new DOMXPath($dom);
		$actions_dom = $xpath->query("//table[contains(@class, 'basic')]/tr[td]");
		$i = 0;
		foreach ($actions_dom as $action_dom) {
			/* Action parsing */
			$tds = $action_dom->getElementsByTagName('td');
			if ($tds->length != 2)
				log_f('parsing error: Action table td count', $this->getId());
			else {
				$this->children[$i]['start_time'] = $this->clean($tds->item(0)->nodeValue);
				list($type, $meta) = $this->parseAction($tds->item(1));
				$this->children[$i]['type'] = $type;
				$this->children[$i]['meta'] = $meta;
				if ($i !== 0) {
					$this->children[$i - 1]['end_time'] = $this->children[$i]['start_time'];
				}
				else {
					$this->start_time = $this->date . ' ' . $this->children[$i]['start_time'];
				}
				$i++;
			}
			/* Action parsing end */
		}
		if ($i > 0) {
			/* If at least 1 action - set last action start time = end time & question end_time = action end time */
			$this->children[$i - 1]['end_time'] = $this->children[$i - 1]['start_time'];
			$this->end_time = $this->date . ' ' . $this->children[$i - 1]['end_time'];
		}
		else {
			/* if no actions - set end time as the start time of the next question */
			$this->end_time = $this->getSiblingInfoByPosition($this->getId(), +1, 'getStartTime');
		}
		unset($xpath);
	}

	protected function parseAction(DOMElement $element) {
		$meta = array();
		$type = 'other';
		//action type - speech
		if (strpos($element->nodeValue, 'Kalbėjo') !== false) {
			$type = 'speech';
			$meta['speaker'] = $this->getMemberId($element);						
		}
		//action type - voting (together)
		elseif (strpos($element->nodeValue, 'bendru sutarimu') !== false) {
			$type = 'u_voting';
			$meta['text'] = $this->decode($element->nodeValue);
			if (strpos($element->nodeValue, 'pritarta') !== false) {
				$meta['outcome'] = 'accepted';
			}
			else {
				$meta['outcome'] = 'rejected';
			}
		}
		//action type - registration
		elseif (stripos($element->nodeValue, 'Įvyko registracija') !== false) {
			$type = 'registration';
			$matches = array();
			$total_participants = preg_match('/užsiregistravo.*?(\d+)/u', $element->nodeValue, $matches);
			if (isset($matches[1]))
				$meta['total_participants'] = $matches[1];

			$reg_link = $element->getElementsByTagName('a')->item(0);
			if (!is_object($reg_link))
				log_f('parsing error: question - registration link', $this->getId());
			else {
				$link = self::BASE_URL . $reg_link->getAttribute('href');
				$meta['link'] = $link;
				$query = parse_url($link, PHP_URL_QUERY);
				$variables = array();
				parse_str($query, $variables);
				if (isset($variables['p_reg_id']))
					$meta['id'] = -$variables['p_reg_id'];
				$meta['participation'] = $this->getRegistrationData($link);
			}
		}
		//action type voting
		elseif (stripos($element->nodeValue, 'Įvyko balsavimas') !== false) {
			$type = 'voting';
			/* general outcome of voting */
			if (strpos($element->nodeValue, 'pritarta') !== false) {
				$meta['outcome'] = 'accepted';
			}
			else {
				$meta['outcome'] = 'rejected';
			}
			/* individual outcome of voting */
			$voting_link = $element->getElementsByTagName('a')->item(0);
			if (!is_object($voting_link))
				log_f('parsing error: question - voting link', $this->getId());
			else {
				$link = self::BASE_URL . $voting_link->getAttribute('href');
				$meta['link'] = $link;
				$query = parse_url($link, PHP_URL_QUERY);
				$variables = array();
				parse_str($query, $variables);
				if (isset($variables['p_bals_id']))
					$meta['id'] = -$variables['p_bals_id'];
				list($meta['voting_topic'], $meta['individual_voting']) = $this->getVotingData($link);
			}			
		}
		else {
			$meta['text'] = $element->nodeValue;
		}

		return array($type, $meta);
	}

	protected function getRegistrationData($url) {
		$lankomumas_dom = $this->getHTMLDOM($url);		
		$xpath = new DOMXPath($lankomumas_dom);
		$nariai = array();
		$nariai_dom = $xpath->query("//table[contains(@cellpadding, '1')]//table[contains(@width, '100%')]/tr");
		//echo $nariai_dom->length;
		foreach ($nariai_dom as $nario_data) {

			$tds = $nario_data->getElementsByTagName('td');
			$participation = false;
			$person_id = false;

			$state = $tds->item(0);
			if (is_object($state)) {
				$value = $this->clean($state->nodeValue);
				if (empty($value))
					$participation = 0;
				else
					$participation = 1;
			}
			else
				log_f('parsing error: action lankomumo lentele - participation . ', $this->getId());

			$person_id = $this->getMemberId($tds->item(1));

			if (($participation !== false) && ($person_id !== false)) {
				$nariai[$person_id] = $participation;
			}
		}
		unset($lankomumas_dom);
		return $nariai;
	}

	protected function getVotingData($url) {		
		$lankomumas_dom = $this->getHTMLDOM($url);				
		$nariai = array();
		$formuluote = '';
		
		//get formuluote
		$inner_html = $this->decode(DOMinnerHTML($lankomumas_dom->getElementsByTagName('body')->item(0)));		
		preg_match("/Formuluot.+?\s+<b>(.*?)<\/b>/msu", $inner_html , $matches);
		if (isset($matches[1])) $formuluote = $matches[1];
		
		//get voting data
		$xpath = new DOMXPath($lankomumas_dom);
		$voting_dom = $xpath->query("//table[contains(@class, 'basic')]/tr[td]");		
		foreach ($voting_dom as $member) {
			$narys = array();
			$td2 = '';
			$td3 = '';
			$td4 = '';
			$tds = $member->getElementsByTagName('td');
			if ($tds->length != 5) log_f('parsing error: voting data', $this->getId ());
			else {
				$narys['id'] = $this->getMemberId($tds->item(0));
				$narys['frakcija'] = $this->clean($tds->item(1)->nodeValue);
				$td2 = $this->clean($tds->item(2)->nodeValue);
				$td3 = $this->clean($tds->item(3)->nodeValue);
				$td4 = $this->clean($tds->item(4)->nodeValue);
				if (!empty($td2)) $narys['vote'] = 'accept';
				elseif (!empty($td3)) $narys['vote'] = 'reject';
				elseif (!empty($td4)) $narys['vote'] = 'abstain';
				else $narys['vote'] = 'missing';
				$nariai[$narys['id']] = $narys;
			}			
		}									
		unset($xpath);
		return array($formuluote, $nariai);
	}
	
	public function getStartTime() {		
		return $this->start_time;
	}

	public function getEndTime() {
		if (empty($this->end_time)) {
			$this->initialise();
		}
		return $this->end_time;
	}
}

?>
