<?php

namespace Seimas;
use \Log;
use Seimas\DOM\DOMXPath;

class SessionScraper extends AbstractScraper {
	protected $pattern = '#http:\/\/www3\.lrs\.lt\/pls\/inter\/w5_sale\.ses_pos\?p_ses_id=(-?[0-9]+)#';
	
	protected function parse(DOMXPath $xpath, Session $session) {
		$this->setMetaData($xpath->document, $session);
		$session->setChildrenData($this->parseSittingLinks($xpath));
	}
	
	protected function parseSittingLinks(DOMXPath $xpath) {	
		$sittings_dom = $xpath->query("//table[contains(@class, 'basic')]/tr/td[last()]/a[contains(@href, 'p_fakt_pos_id')]/@href");
		foreach ($sittings_dom as $link) {
			$sitting_url = $link->nodeValue;
			if ($sitting_url !== null) {
				yield $sitting_url;
			} else {
				Log::error('Sitting link not recognised', ['url' => $link->nodeValue]);
			}
		}
		unset($sittings_dom);
	}
	
	protected function setMetaData(\DOMDocument $dom, Session $session) {
		$matches = [];
		preg_match(
			"/(\d) ((ne)?(eilinė)) Seimo sesija \((.*) - (.*)\)/u",
			$dom->getElementsByTagName('title')->item(0)->nodeValue,
			$matches
		);		
		$session->number = $matches[1];
		$session->type = $matches[2];		
		$session->start_date = new \DateTime(trim($matches[5]), new \DateTimeZone('Europe/Vilnius'));
		if ($matches[6] != '...') {
			 $session->end_date = new \DateTime(trim($matches[6]), new \DateTimeZone('Europe/Vilnius'));
		}
	}
	
}
