<?php

namespace Seimas\parsers;
use Seimas\models\Action;
use Seimas\DOM\DOMXPath, Seimas\DOM\DOMElement;
use Log as Log;

class ActionParser extends AbstractParser {
	
	public function parse($action, DOMXPath $xpath = null, DOMElement $element = null) {
		if (!($action instanceof Action)) {
			throw new \InvalidArgumentException(
					__CLASS__ . ' only accepts Action objects, ' 
					. get_class($action) . ' given instead.'
			);
		} elseif ($action->type == Action::OTHER) {
			Log::warning('Unknown action type found', ['action' => $action]);
		} elseif ($action->type !== Action::ALTERNATE_VOTE) {
			$concrete_parser = $this->factory->resolve($action);
			if ($concrete_parser === null) {
				Log::error('Concrete Action parser not found', ['context' => $action]);
			} elseif (get_class($concrete_parser) == 'Seimas\parsers\ActionParser') {
				\Debugbar::info('Avoiding infinite loop!!!', ['action' => $action]);
			} else {
				$concrete_parser->parse($action, $xpath, $element);
			}			
		}
	}
}
