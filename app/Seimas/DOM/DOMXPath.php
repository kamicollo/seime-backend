<?php

namespace Seimas\DOM;

class DOMXPath extends \DOMXPath {
	
	public function getHrefByLinkName($linkName) {
		$anchor = $this->query("//a[.='" . $linkName . "']")->item(0);		
		if (is_object($anchor)) {
			return $anchor->getAttribute('href');
		}
	}
}
