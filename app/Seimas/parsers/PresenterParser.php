<?php

namespace Seimas\parsers;
use Seimas\DOM\DOMXPath, Seimas\DOM\DOMElement;
use Seimas\models\Presenter;
use \Log as Log;

class PresenterParser extends AbstractParser {
	
	public function parse($presenter, DOMXPath $main_xpath = null, DOMElement $element = null) {
		if (!($presenter instanceof Presenter)) {
			throw new \InvalidArgumentException(
					__CLASS__ . ' only accepts Presenter objects, ' 
					. get_class($presenter) . ' given instead.'
			);
		} else {
			$presenter->presenter = $element->getTrimmedValue();
		}
	}
	
}
