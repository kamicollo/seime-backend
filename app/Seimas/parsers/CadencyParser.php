<?php

namespace Seimas\parsers;
use Seimas\DOM\DOMXPath, Seimas\DOM\DOMElement, Seimas\DOM\DOMDocument;
use Seimas\models\Cadency;
use \Log as Log;

class CadencyParser extends AbstractParser {
	
	public function parse($cadency, DOMXPath $main_xpath = null, \ArrayObject $data = null) {
		if (!($cadency instanceof Cadency)) {
			throw new \InvalidArgumentException(
					__CLASS__ . ' only accepts Cadency objects, ' 
					. get_class($cadency) . ' given instead.'
			);
		} else {
			$cadency->setChildrenData($this->parseSessions($data, $cadency));
		}
	}
	
	protected function parseSessions($data, $cadency) {
		$xpath = $data[0];
		$context_node = $data[1];
		$next_context_node = $data[2];
		$node_expression = ".//parent::tr[contains(., '" . $context_node->getValue() . "')]";
		if ($next_context_node !== null) {
			$next_node_expression = ".//parent::tr/following-sibling::tr[contains(., '" . $next_context_node->getValue() . "')]";
			$expression = "$node_expression/following-sibling::tr
				[count(.|$next_node_expression/preceding-sibling::tr)
				=
				 count($next_node_expression/preceding-sibling::tr)
				]";
		} else {
			$expression = "$node_expression/following-sibling::tr";
		}
		$nodes = $xpath->query($expression, $context_node);
		if ($nodes->length == 0) {
			Log::error('No sessions found for cadency!', ['cadency' => $cadency]);
		} else {
			foreach($nodes as $n) {
				$bag =  $this->parseSessionRow($n, $cadency->years);
				if ($bag !== null) {
					yield $bag;
				}
			}
		}
	}
	
	protected function parseSessionRow(DOMElement $node, $years) {
		$cells = $node->getElementsByTagName('td');
		if ($cells->length !== 3) {
			Log::error('Unable to parse session data in the cadency table', ['html' => $node->saveHTML()]);
		} elseif ($cells->item(0)->getElementsByTagName('a')->length == 0) {
			Log::error('Unable to parse session url in the cadency table', ['html' => $node->saveHTML()]);
		} else {
			$url = $cells->item(0)->getElementsByTagName('a')->item(0)->getAttribute('href');
			return new \Seimas\DataBag(
					null,
					$url,
					new \ArrayObject(['kadencija' => $years, 'url' => $url], \ArrayObject::ARRAY_AS_PROPS)
			);
		}
	}
}
