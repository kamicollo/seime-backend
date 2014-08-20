<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Session extends HTMLObject {
	
	static public $create_sql = 'SELECT *, 1 as PDO FROM `sessions` WHERE id = ?';
	static public $children_sql = 'SELECT *, 1 as PDO FROM `sittings` where sessions_id = ?';		
	static public $child_class = 'sitting';
	static public $url_token = 'p_ses_id=';
	
	
	protected $number;
	protected $type;
	protected $start_date;
	protected $end_date;	
	
	protected function populateData() { 
		if ($this->PDO) {
			$this->populateChildren();			
			return true;			
		}
		else return false;
	}	

	public function saveData() {
		$array = get_object_vars($this);
		unset($array['PDO']);
		unset($array['Factory']);
		unset($array['parent']);
		unset($array['url_token']);
		$children_array = array();
		foreach ($array['children'] as $child) {
			$children_array[] = array('id' => $child->getId(), 'url' => $child->getUrl(), 'sessions_id' => $this->getId());
		}
		unset($array['children']);
		$this->Factory->SaveObject('sessions', $array, array('id'));
		$this->Factory->SaveObjects('sittings', $children_array, array('id', 'sessions_id'));				
	}
	
	public function scrapeData($reload = FALSE) {
		$dom = $this->getHTMLDOM($this->url, $reload);
		$this->getMetaData($dom);
		$this->getSittings($dom);
	}
	
	private function getSittings(DOMDocument $dom) {
		$xpath = new DOMXPath($dom);		
		$sittings = array();
		$sittings_dom = $xpath->query("//table[contains(@class, 'basic')]/tr/td[last()]/a[contains(@href, 'p_fakt_pos_id')]/@href");
		foreach ($sittings_dom as $link) {			
			$sitting = $this->Factory->getObject(self::$child_class, self::BASE_URL . $link->nodeValue, '', $this);			
			$sittings[$sitting->getId()] = $sitting;
		}
		$this->children = $sittings;
		unset($xpath);
	}
	
	private function getMetaData(DOMDocument $dom) {
		$title = $dom->getElementsByTagName('title')->item(0)->nodeValue;
		$matches = array();
		preg_match("/(\d) ((ne)?(eilinė)) Seimo sesija \((.*) - (.*)\)/u", $title, $matches);		
		$this->number = $matches[1];
		$this->type = $matches[2];		
		$this->start_date = trim($matches[5]);
		if ($matches[6] != '...') $this->end_date = trim($matches[6]);
		else $this->end_date = false;				
	}	
	
	public function getType() {
		return $this->type;
	}
	
	public function getNumber() {
		return $this->number;
	}
}

?>
