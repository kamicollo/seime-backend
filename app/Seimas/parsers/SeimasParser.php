<?php

namespace Seimas\parsers;
use Seimas\DOM\DOMXPath, Seimas\DOM\DOMElement, Seimas\DOM\DOMDocument;
use Seimas\models\Seimas;
use \Log as Log;

class SeimasParser extends AbstractParser {
	
	public function parse($seimas, DOMXPath $xpath = null) {
		if (!($seimas instanceof Seimas)) {
			throw new \InvalidArgumentException(
					__CLASS__ . ' only accepts Seimas objects, ' 
					. get_class($seimas) . ' given instead.'
			);
		} else {
			$seimas->setChildrenData($this->parseCadencies($xpath));
		}
	}
	
	protected function parseCadencies(DOMXPath $xpath) {
		$nodes = $xpath->query('//td[@colspan = 3 and @class = "ctb"]/parent::tr');
		for ($i = 0; $i < $nodes->length; $i++) {
			$node = $nodes->item($i);
			$matches = [];
			if (preg_match('/([0-9]{4}) - ([0-9]{4})/', $node->getTrimmedValue(), $matches)) {
				$data = new \ArrayObject(
						['years' => $matches[1] . '-' . $matches[2]],
						\ArrayObject::ARRAY_AS_PROPS
				);
				yield new \Seimas\DataBag(null, null, $data, new \ArrayObject([$xpath, $node, $nodes->item($i + 1)]));
			} else {
				Log::Error(
					'Could not parse the cadency table. All is lost. Powers that be took over the world.'
				);
			}
		}
	}
}
