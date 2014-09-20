<?php

namespace Seimas;
use \Log;
use Seimas\DOM\DOMElement;

class SpeechScraper extends AbstractScraper {
	
	public function __construct($http_client) {
		$this->http_client = $http_client;
	}
	
	public function parse(DOMElement $element, Speech $object) {
		$anchor = $element->getElementsByTagName('td')->item(1)->getElementsByTagName('a')->item(0);
		if ($anchor instanceof DOMElement) {
			$object->speaker = MemberFactory::createFromAnchor($anchor);
		} else {
			Log::warning(
				'Could not determine speaker identity in Speech Action',
				['object' => $object, 'element' => $element]
			);
		}		
	}
}
