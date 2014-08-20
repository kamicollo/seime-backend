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
	
	//modified implementation of abstractions.php
	public function populateChildren($initialiseSearch = false) {
		$class = get_class($this);
		$class_ = new ReflectionClass($class);
		$token = $class_->getStaticPropertyValue('child_class');
		$children = $this->Factory->getObjectChildren($class, $token, $this->getId(), $this);
		foreach ($children as $child) {
			$this->children[$child->getNumber()] = $child;
		}
		if (empty($this->children) && ($initialiseSearch)) {			
			$this->scrapeData();
			$this->saveData();
		}
	}
	
	protected function populateItems() {
		$class = get_class($this);
		$class_ = new ReflectionClass($class);
		$items_sql = $class_->getStaticPropertyValue('items_sql');
		$presenters_sql = $class_->getStaticPropertyValue('presenters_sql');
		$items = $this->Factory->getArray($items_sql, array($this->getId()));
		foreach ($items as $item) {			
			$presenters = $this->Factory->getArray($presenters_sql, array($item['id']));
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
		
		foreach ($this->children as $child) {
			$child->saveMainData();			
		}
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
		
	protected function scrapeData($reload = FALSE) {
		$dom = $this->getHTMLDOM($this->url, $reload);
		$this->getItems($dom);		
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
			$matches = array();
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
			/* Create Children Actions */
				// dirty hack for avoiding Factory exception of no ID & URL
			$this->children[$i] = $this->Factory->getObject(self::$child_class, 'http://fake-url.lt/', '', $this, array('dom' => $action_dom, 'id' => $i));
			$i++;
		}		
		$this->initialiseChildrenParse();
		/* get end_time of a question */		
		try {
			//try to get the start time of next question
			$this->end_time = $this->getSiblingInfoByPosition($this->getId(), +1, 'getStartTime');
		}
		catch(Exception $e) {
			//if no success - probably last question. Try end time of last children, if any
			if ($i > 0) {			
				$this->end_time = $this->date . ' ' . $this->children[$i - 1]->getEndTime();
			}
			else {
			//if no actions - set end time as the start time of the next question 
				$this->end_time = $this->start_time;
			}								
		}
		unset($xpath);
	}
	
	protected function initialiseChildrenParse() {
		foreach ($this->children as $child) {
			$child->parseData();
		}
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
	
	public function getTitle() {
		return $this->title;
	}
		
}

?>
