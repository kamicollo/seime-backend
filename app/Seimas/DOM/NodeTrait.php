<?php

namespace Seimas\DOM;

trait NodeTrait {	
	public function getNodeValue() {
		\Debugbar::info(debug_backtrace()[1]['function']);
		$value = html_entity_decode($this->nodeValue, ENT_QUOTES, 'UTF-8');
		return preg_replace('/\s+/', ' ', trim($value));
	}
	
	public function getValue() {
		return $this->nodeValue;
	}
	
	public function getSafeValue() {
		return htmlentities($this->fixWhitespace($this->nodeValue), ENT_NOQUOTES, 'UTF-8');
	}
	
	protected function fixWhitespace($value) {
		return trim(preg_replace('/(\s+)|(\xA0+)/u', ' ', $value));
	}
	
	public function getTrimmedValue() {
		return $this->fixWhitespace($this->nodeValue);
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
