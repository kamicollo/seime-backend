<?php

namespace Seimas\DOM;

trait NodeTrait {	
	public function getNodeValue() {
		$value = html_entity_decode($this->nodeValue, ENT_QUOTES, 'UTF-8');
		return preg_replace('/\s+/', ' ', trim($value));
	}
	
	public function __toString() {
		$newdoc = new \DOMDocument;
		$node = $newdoc->importNode($this, true);
		$newdoc->appendChild($node);
		return $newdoc->saveHTML();
	}
	
	public function saveHTML() {
		return $this->__toString();
	}
}
