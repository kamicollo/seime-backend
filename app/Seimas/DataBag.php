<?php

namespace Seimas;

class DataBag {
	
	/** @var string */
	protected $className;
	
	/** @var string */
	protected $url;
	
	/** @var \ArrayObject */
	protected $initData;
	
	/** @var mixed */
	protected $domxpath;
	
	public function __construct($class, $url = null, \ArrayObject $init_data = null, $parsing_data = null) {
		$this->className = $class;
		$this->url = $url;
		if ($init_data !== null) {
			$this->initData = $init_data;
		} else {
			$this->initData = new \ArrayObject();
		}
		$this->domxpath = $parsing_data;
	}
	
	public function getClassName() {
		return $this->className;
	}

	public function getUrl() {
		return $this->url;
	}

	public function getInitData() {
		return $this->initData;
	}

	public function getDomxpath() {
		return $this->domxpath;
	}

	public function setClassName($className) {
		$this->className = $className;
		return $this;
	}

	public function setUrl($url) {
		$this->url = $url;
		return $this;
	}

	public function setInitData(\ArrayObject $initData) {
		$this->initData = $initData;
		return $this;
	}

	public function setDomxpath($domxpath) {
		$this->domxpath = $domxpath;
		return $this;
	}	
	
}
