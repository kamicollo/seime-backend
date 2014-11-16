<?php

namespace Seimas\parsers;
use Seimas\DOM\DOMXPath, Seimas\DOM\DOMElement;
use Seimas\models\Speech;
use Seimas\factories\MemberFactory;
use \Log as Log;

class SpeechParser extends AbstractParser {
	
	public function parse($speech, DOMXPath $xpath = null, DOMElement $element = null) {
		if (!($speech instanceof Speech)) {
			throw new \InvalidArgumentException(
					__CLASS__ . ' only accepts Speech objects, ' 
					. get_class($speech) . ' given instead.'
			);
		} else {
			$anchor = $element->getElementsByTagName('td')->item(1)->getElementsByTagName('a')->item(0);
			if ($anchor instanceof DOMElement) {
				$speech->speaker = MemberFactory::createFromAnchor($anchor);
			} else {
				Log::warning(
					'Could not determine speaker identity in Speech Action',
					['object' => $speech, 'element' => $element]
				);
			}		
		}
	}
	
}
