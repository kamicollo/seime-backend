<?php

namespace Seimas\parsers;
use Seimas\DOM\DOMXPath, Seimas\DOM\DOMElement, Seimas\DOM\DOMDocument;
use Seimas\models\Session;
use \Log as Log;

class SessionParser extends AbstractParser {
	
	public function parse($session, DOMXPath $xpath = null) {
		if (!($session instanceof Session)) {
			throw new \InvalidArgumentException(
					__CLASS__ . ' only accepts Session objects, ' 
					. get_class($session) . ' given instead.'
			);
		} else {
			$this->setMetaData($xpath->document, $session);
			$session->setChildrenData($this->parseSittingLinks($xpath));
		}
	}
	
	protected function setMetaData(\DOMDocument $dom, Session $session) {
		$matches = [];
		preg_match(
			"/(\d) ((ne)?(eilinė)) Seimo sesija \((.*) - (.*)\)/u",
			$dom->getElementsByTagName('title')->item(0)->getTrimmedValue(),
			$matches
		);		
		$session->number = $matches[1];
		$session->type = $matches[2];		
		$session->start_date = new \DateTime(trim($matches[5]), new \DateTimeZone('Europe/Vilnius'));
		if ($matches[6] != '...') {
			 $session->end_date = new \DateTime(trim($matches[6]), new \DateTimeZone('Europe/Vilnius'));
		}
	}
	
	protected function parseSittingLinks(DOMXPath $xpath) {	
		$sittings_dom = $xpath->query("//table[contains(@class, 'basic')]/tr/td[last()]/a[contains(@href, 'p_fakt_pos_id')]/@href");
		foreach ($sittings_dom as $link) {
			$sitting_url = $link->getValue();
			if ($sitting_url !== null) {
				yield new \Seimas\DataBag(
					null,
					$sitting_url,
					new \ArrayObject(['url' => $sitting_url])
				);
			} else {
				Log::error('Sitting link not recognised', ['url' => $link->getValue()]);
			}
		}
	}
}
