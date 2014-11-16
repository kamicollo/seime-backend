<?php

namespace Seimas\DOM;
class DOMDocument extends \DOMDocument {
	
	public function __construct ($version = null,  $encoding = null) {
		parent::__construct($version, $encoding);
		$this->registerNodeClass('DOMNode', 'Seimas\DOM\DOMNode');
		$this->registerNodeClass('DOMElement', 'Seimas\DOM\DOMElement');
		$this->registerNodeClass('DOMText', 'Seimas\DOM\DOMText');
		$this->registerNodeClass('DOMAttr', 'Seimas\DOM\DOMAttr');
		//not implemented in PHP just yet :/
		//$this->registerNodeClass('DOMNodeList', 'Seimas\DOM\DOMNodeList');
	}
	
	public function loadDirtyHTML($raw_html, $encoding) {
		return @$this->loadHTML($this->repairHTML($this->convertToUnicode($raw_html, $encoding)));
	}
	
	public function replaceRelativeURLs($base_url) {
		foreach($this->getElementsByTagName('a') as $anchor) {
			$href = $anchor->getAttribute('href');
			if (filter_var($href, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED) == false) {
				$anchor->setAttribute('href', $base_url . $href);
			}
		}
	}
	
	protected function convertToUnicode($html, $encoding) {
		$xhtml = str_replace(chr(165), '', $html);
		$html_new = @iconv($encoding, 'UTF-8//IGNORE', $xhtml);
		if ($html_new === false) {
			ini_set('mbstring.substitute_character', "none"); 
			$html_new = mb_convert_encoding($xhtml, 'UTF-8', $encoding); 
		}
		return str_replace('charset=' . $encoding, 'charset=UTF-8', $html_new);
	}
	
	protected function repairHTML($html) {
		$tidy = new \tidy;
		$config = array(
			'indent' => true,
			'doctype' => 'loose',
			'output-xhtml' => true,
			'output-encoding' => 'utf8',
			'wrap' => 200);
		$tidy->parseString($html, $config, 'utf8');
		$tidy->cleanRepair();
		return (string) $tidy;
	}
}
