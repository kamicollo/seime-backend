<?php

namespace Seimas\parsers;
use Seimas\DOM\DOMXPath, Seimas\DOM\DOMElement;

abstract class AbstractParser {
	/** @var \Seimas\factories\ParserFactory */
	protected $factory = null;
	abstract public function parse($object, DOMXPath $xpath = null);
	
	public function __construct(\Seimas\factories\ParserFactory $factory) {
		$this->factory = $factory;
	}
}
