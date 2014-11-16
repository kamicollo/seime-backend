<?php

namespace Seimas\parsers;
use Seimas\DOM\DOMXPath, Seimas\DOM\DOMElement;
use Seimas\models\Registration;
use \Log as Log;

class RegistrationParser extends AbstractParser {
	
	public function parse($registration, DOMXPath $xpath = null, DOMElement $element = null) {
		if (!($registration instanceof Registration)) {
			throw new \InvalidArgumentException(
					__CLASS__ . ' only accepts Registration objects, ' 
					. get_class($registration) . ' given instead.'
			);
		} else {
			$registration->total_participants = $this->parseParticipantCount($registration->title);
			$registration->url = $this->parseRegistrationLink($element);
			if ($registration->url !== null) {
				$registration->setRegistrationData($this->createGenerator($registration->url));
			} else {
				Log::warning(
					'could not find registration action url',
					['object' => $registration, 'element' => $element]
				);
			}	
		}
	}
	
	protected function createGenerator($url) {
		yield new \Seimas\DataBag(null, $url);
	}
	
	protected function parseParticipantCount($title) {
		$matches = array();
		preg_match('/užsiregistravo.\s*(\d+)/u', $title, $matches);
		if (isset($matches[1])) {
			return $matches[1];
		} else {
			return 0;
		}
	}
	
	protected function parseRegistrationLink(DOMElement $element) {
		$reg_link = $element->getElementsByTagName('td')->item(1)->getElementsByTagName('a')->item(0);
		if ($reg_link instanceof DOMElement) {
			 return $reg_link->getAttribute('href');	
		}
	}
}
