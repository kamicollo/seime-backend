<?php

namespace Seimas\parsers;

use Seimas\DOM\DOMXPath, Seimas\DOM\DOMElement;
use Seimas\models\Action;
use \Log as Log;

class QuestionParser extends AbstractParser {
	
	public function parse($question, DOMXPath $xpath = null, DOMElement $element = null) {
		if (!($question instanceof \Seimas\models\Question)) {
			throw new \InvalidArgumentException(
					__CLASS__ . ' only accepts Question objects, ' 
					. get_class($question) . ' given instead.'
			);
		} else {
			$question->setItemData($this->parseItems($xpath));
			$question->setChildrenData($this->parseActions($xpath));
		}
	}
	
	protected function parseActions(DOMXPath $xpath) {
		$actions_dom = $xpath->query("//table[contains(@class, 'basic')]/tr[td]");
		for ($i = 0; $i < $actions_dom->length; $i++) {
			$action_element = $actions_dom->item($i);
			yield $this->parseAction($action_element, $i);
		}
	}
	
	protected function parseAction(DOMElement $element, $number) {
		$metaData = new \ArrayObject([], \ArrayObject::ARRAY_AS_PROPS);
		$metaData->number = $number;
		$metaData->title = $this->getActionTitle($element);
		$metaData->type = $this->getActionType($metaData->title);
		$tables = $element->getElementsByTagName('td');
		if ($tables->length == 2) {
			$metaData->start_time = $tables->item(0)->getTrimmedValue();
		}
		return new \Seimas\DataBag($metaData->type, null, $metaData, $element);	
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
			return $tables->item(1)->getTrimmedValue();
		}
	}
	
	protected function parseItems(DOMXPath $xpath) {
		$inner_questions = $xpath->query("//li[preceding::h4 and following::h4]");
		if ($inner_questions->length > 0) {
			for($i = 0; $i < $inner_questions->length; $i++) {
				 $context = $inner_questions->item($i);
				yield new \Seimas\DataBag(null, null, new \ArrayObject(['number' => $i + 1]), new \ArrayObject([$xpath, $context]));
			}
		} else  {
			yield new \Seimas\DataBag(null, null, new \ArrayObject(['number' => 1]), clone new \ArrayObject([$xpath, null]));
		}
	}
}
