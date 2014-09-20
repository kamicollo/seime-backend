<?php

namespace Seimas;
use \Log as Log;
use Seimas\DOM\DOMElement as DOMElement;

class ActionScraper extends AbstractScraper {
	
	public function scrapeMany(\Generator $children, \Closure $childfactory, \Closure $cb = null, \Closure $error_cb = null) {
		foreach($children as $child) {
			$object = $this->initAction($childfactory, $child);
			$scraper = $this->resolveScraper($object->type);
			$scraper->parse($child['dom_object'], $object);
		}
	}
			
	protected function initAction(\Closure $factory, $child_data) {
		$dom_object = $child_data['dom_object'];
		$action_title = $this->getActionTitle($dom_object);
		$action_type = $this->getActionType($action_title);
		$object = $factory($action_type);
		$object->type = $action_type;
		$object->number = $child_data['number'];
		$object->title = $action_title;
		$tables = $dom_object->getElementsByTagName('td');
		if ($tables->length == 2) {
			$object->start_time = $this->clean($tables->item(0)->nodeValue);
		}
		return $object;
	}
	
	protected function resolveScraper($action_type) {
		switch ($action_type) {
			case Action::REGISTRATION:
				return new RegistrationScraper($this->http_client);
			case Action::SPEECH:
				return new SpeechScraper($this->http_client);
			case Action::UNANIMOUS_VOTE:
				return new UnanimousVoteScraper($this->http_client);
			case Action::VOTE:
				return new VoteScraper($this->http_client);
			default:
				return $this;
		}
	}
	
	protected function getActionType($title) {
		if (stripos($title, 'Kalbėjo') !== false) { //action type - speech
				return Action::SPEECH;
		} elseif (stripos($title, 'alternatyvus balsavimas') !== false) { //action type - voting (alternate choices)
			return Action::ALTERNATE_VOTE;
		} elseif (stripos($title, 'bendru sutarimu') !== false) { //action type - voting (together)
			return Action::UNANIMOUS_VOTE;
		} elseif (stripos($title, 'Įvyko registracija') !== false) { //action type - registration
			return Action::REGISTRATION;
		} elseif (stripos($title, 'Įvyko balsavimas') !== false) { //action type voting
			return Action::VOTE;
		} else {
			return Action::OTHER;
		}
	}
	
	protected function getActionTitle(DOMElement $element) {
		$tables = $element->getElementsByTagName('td');
		if ($tables->length != 2) {
			Log::warning(
				'Unable to determine Action type', 
				['html' => $element]
			);
			return '';
		} else {
			return $tables->item(1)->getNodeValue();
		}
	}
	
	protected function setMetaData(DOMElement $dom, $number, $title, Action $action) {
		$action->number = $number;
		$action->title = $title;
		$tables = $dom->getElementsByTagName('td');
		if ($tables->length == 2) {
			$action->start_time = $this->clean($tables->item(0)->nodeValue);
		} 
	}
	
	protected function parse(DOMElement $element, Action $object) {
		if ($object->type == ACTION::OTHER) {
			Log::info($object);
		}
	}
	
}
