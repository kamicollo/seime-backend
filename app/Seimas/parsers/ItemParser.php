<?php

namespace Seimas\parsers;
use Seimas\DOM\DOMXPath, Seimas\DOM\DOMElement;
use Seimas\models\Item;
use \Log as Log;

class ItemParser extends AbstractParser {
	
	public function parse($item, DOMXPath $main_xpath = null, \ArrayObject $data = null) {
		if (!($item instanceof Item)) {
			throw new \InvalidArgumentException(
					__CLASS__ . ' only accepts Item objects, ' 
					. get_class($item) . ' given instead.'
			);
		} else {
			$xpath = $data[0];
			$context = $data[1];
			$this->setTitle($item, $this->getTitle($xpath, $context));
			$this->setDocURL($item, $this->getDocURL($xpath, $context));
			$this->setRelatedURLs($item, $this->getRelatedDocURLs($xpath, $context));
			$item->setChildrenData($this->parsePresenters($xpath, $context));
		}
	}
	
	protected function setTitle(Item $item, $title) {
		if ($title === null) {
			Log::warning('Could not find Item title', ['object' => $item]);
		} elseif ($item->title == null) {
			$item->title = $title;
		}
	}
	
	protected function setDocURL(Item $item, $doc_url) {
		if (($doc_url === null) && (!$this->specialItem($item))) {
			Log::notice('Could not find Item document url', ['object' => $item]);
		} else {
			$item->document_url = $doc_url;
		}
	}
	
	protected function setRelatedURLs(Item $item, $related_url) {
		if (($related_url === null) && (!$this->specialItem($item))) {
			Log::notice('Could not find Item related document url', ['object' => $item]);
		} else {
			$item->related_doc_url = $related_url;
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
			return $nodes->item(0)->getValue();
		} 
	}
	
	protected function getDocURL(DOMXPath $xpath, DOMElement $context = null) {
		$nodes = $xpath->query("(.//a[contains(@href,'dokpaieska.showdoc_l')])[1]/@href", $context);
		if ($nodes->length > 0) {
			return $nodes->item(0)->getValue();
		}
	}
	
	protected function getRelatedDocURLs(DOMXPath $xpath, DOMElement $context = null) {
		$nodes = $xpath->query("(.//a[contains(@href,'dokpaieska.susije_l')])[1]/@href", $context);
		if ($nodes->length > 0) {
			return $nodes->item(0)->getValue();
		}
	}
	
	protected function parsePresenters(DOMXPath $xpath, DOMElement $context = null) {
		$nodes = $xpath->query('.//b[preceding-sibling::node()[contains(., "Pranešėja")]]', $context);
		for($i = 0; $i < $nodes->length; $i++) {
			yield new \Seimas\DataBag(
				null,
				null,
				new \ArrayObject(['number' => $i + 1], \ArrayObject::ARRAY_AS_PROPS),
				$nodes->item($i)
			);
		}
	}
	
	protected function specialItem(Item $item) {
		return (
			(stripos($item->title, 'Vyriausybės valanda') !== false) ||
			(stripos($item->title, 'darbotvarkės tvirtinimas') !== false) ||
			(stripos($item->title, 'prisaikdinimas') !== false) ||
			(stripos($item->title, 'priesaika') !== false) ||
			(stripos($item->title, 'pranešimas') !== false) ||
			(stripos($item->title, 'minėjimas') !== false) ||
			(stripos($item->title, 'protokolinio nutarimo') !== false) ||
			(stripos($item->title, 'Seimo narių pareiškimai') !== false) ||
			(stripos($item->title, 'kalbos ir sveikinimai') !== false) ||
			(stripos($item->title, 'rinkimai') !== false) ||
			false
		);
	}
	
}
