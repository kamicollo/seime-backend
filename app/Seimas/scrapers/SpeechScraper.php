<?php

namespace Seimas\scrapers;
use \Log;
use Seimas\DOM\DOMElement;

class SpeechScraper extends AbstractScraper {
	
	public function __construct($http_client) {
		$this->http_client = $http_client;
	}
	
	public function parse(DOMElement $element, Speech $object) {
		
	}
}
