<?php

namespace Seimas;
use \Log as Log;
use Seimas\DOM\DOMElement as DOMElement;

class ItemScraper extends AbstractScraper {
	
	public function scrapeMany(\Generator $children, \Closure $childfactory, \Closure $cb = null, \Closure $error_cb = null) {
		foreach($children as $child) {
			$this->setTitle($question, $this->getTitle($xpath, $context));
				$this->setDocURL($question, $this->getDocURL($xpath, $context));
				$this->setRelatedURLs($question, $this->getRelatedDocURLs($xpath, $context));
		}
	}
	
	
	protected function setTitle(Question $question, $title) {
		if ($title === null) {
			Log::warning('Could not find Question title', ['object' => $question]);
		} elseif ($question->title == null) {
			$question->title = $title;
		}
	}
	
	protected function setDocURL(Question $question, $doc_url) {
		if ($doc_url === null) {
			Log::notice('Could not find Question document url', ['object' => $question]);
		} else {
			$question->document_url = $doc_url;
		}
	}
	
	protected function setRelatedURLs(Question $question, $related_url) {
		if ($related_url === null) {
			Log::notice('Could not find Question related document url', ['object' => $question]);
		} else {
			$question->related_doc_url = $related_url;
		}
	}
	
	protected function getTitle(DOMXPath $xpath, DOMElement $context = null) {
		if ($context == null) {
			$additional_limit = "h4[contains(text(), 'Darbotvarkės klausimas')]/following-sibling::";
		} else {
			$additional_limit = '';
		}
		$nodes = $xpath->query(".//" . $additional_limit . "b[1]/text()", $context);
		if ($nodes->length > 0) {
			return $nodes->item(0)->nodeValue;
		} 
	}
	
	protected function getDocURL(DOMXPath $xpath, DOMElement $context = null) {
		$nodes = $xpath->query("(.//a[contains(@href,'dokpaieska.showdoc_l')])[1]/@href", $context);
		if ($nodes->length > 0) {
			return $nodes->item(0)->nodeValue;
		}
	}
	
	protected function getRelatedDocURLs(DOMXPath $xpath, DOMElement $context = null) {
		$nodes = $xpath->query("(.//a[contains(@href,'dokpaieska.susije_l')])[1]/@href", $context);
		if ($nodes->length > 0) {
			return $nodes->item(0)->nodeValue;
		}
	}
	
	protected function setPresenters(DOMXPath $xpath, Question $question) {
		
	}
}
