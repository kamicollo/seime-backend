<?php

namespace Seimas;

class DescendantBag {
	
	/** @var string */
	protected $class;
	/** @var \Generator */
	protected $generator;

	public function __construct($class, \Generator $generator) {
		$this->class = $class;
		$this->generator = $generator;
	}
	
	/** @return string */
	public function getClass() {
		return $this->class;
	}

	/** @return \Generator */
	public function getGenerator() {
		return $this->generator;
	}
	/** @return \Seimas\DescendantBag */
	public function setClass($class) {
		$this->class = $class;
		return $this;
	}

	/** @return \Seimas\DescendantBag */
	public function setGenerator(\Generator $generator) {
		$this->generator = $generator;
		return $this;
	}


}
