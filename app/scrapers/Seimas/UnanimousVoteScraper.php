<?php

namespace Seimas;

class UnanimousVoteScraper extends AbstractScraper {
	
	public function parse(\DOMElement $element, Action $object) {
		if (stripos($object->title, 'pritarta') !== false) {
			$object->outcome = ACTION::VOTE_OUTCOME_ACCEPT;
		} else {
			$object->outcome = ACTION::VOTE_OUTCOME_REJECT;
		}
	}
	
}
