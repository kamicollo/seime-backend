<?php

namespace Seimas;

use Seimas\DOM\DOMXPath;

abstract class AbstractScraper {
	/** @var string */
	const BaseURL = 'http://www3.lrs.lt/pls/inter/';
	/** @var Seimas\HttpClientInterface */
	protected $http_client;
	
	
	public function __construct(HttpClientInterface $http_client) {
		$this->http_client = $http_client;
	}
	
	public function scrape($url, \Eloquent $object, \Closure $cb = null, \Closure $error_cb = null) {
		if ($this->validUrl($url)) {
			if ($cb === null) {
				$cb = $this->getDefaultCallback();
			}
			if ($error_cb === null) {
				$error_cb = $this->getDefaultErrorCallback();
			}
			$this->http_client->request($url, $cb, $error_cb, $object);
		} else {
			throw new \InvalidArgumentException('Invalid URL passed');
		}
	}
	
	public function scrapeMany(\Generator $urls, \Closure $childfactory, \Closure $cb = null, \Closure $error_cb = null) {
		if ($cb === null) {
			$cb = $this->getDefaultCallback();
		}
		if ($error_cb === null) {
			$error_cb = $this->getDefaultErrorCallback();
		}
		$this->http_client->startAsync();
		foreach ($urls as $url) {
			if ($this->validUrl($url)) {
				$this->http_client->addAsync($url, $childfactory());
			}
		}
		$this->http_client->setAsyncCallbacks($cb, $error_cb);
		$this->http_client->finishAsync();		
	}
	
	protected function getDefaultCallback() {
		return 
			function($html, $url, $params) {
				$this->processResponse($html, $url, $params);					
			};
	}
	
	protected function getDefaultErrorCallback() {
		function($error, $url, $params) {
			$this->handleError(
				'Loading web page failed', 
				['context' => $error, 'object' => $params, 'url' => $url]
			);
		};
	}
		
	public function processResponse($html, $url, \Eloquent $object) {
		try {
			$dom = $this->prepareDomDocument($html);
			$object->url = $url;
			$this->parse(new DOMXPath($dom), $object);
		} catch (\Exception $e) {
			$this->handleError(
				$e->getMessage(),
				['context' => $e, 'object' => $object, 'url' => $url]
			);
		}
	}
	
	protected function prepareDomDocument($raw_html) {
		$dom = $this->getNewDomDocument();
		$success = @$dom->loadHTML($this->repairHTML($this->convertToUnicode($raw_html)));
		if (!$success) {
			$e = new \Exception('Could not load the document');
			$e->data = ['html' => $raw_html];
			throw $e;
		}
		$this->replaceRelativeURLs($dom);
		return $dom;
		/**
		 * @todo Figure out why laravel hides these warnings (it throws an ErrorException, and
		 * I have no idea where it is caught...
		 */
	}
	
	protected function getNewDomDocument() {
		$dom = new \DOMDocument('1.0', 'UTF-8');
		$dom->registerNodeClass('DOMNode', 'Seimas\DOM\DOMNode');
		$dom->registerNodeClass('DOMElement', 'Seimas\DOM\DOMElement');
		return $dom;
	}
	
	protected function replaceRelativeURLs(\DOMDocument $dom) {
		foreach($dom->getElementsByTagName('a') as $anchor) {
			$href = $anchor->getAttribute('href');
			if (filter_var($href, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED) == false) {
				$anchor->setAttribute('href', self::BaseURL . $href);
			}
		}

	}
	
	protected function convertToUnicode($html) {
		$html = @iconv('windows-1257', 'UTF-8//IGNORE', $html);
		return str_replace('charset=windows-1257"', 'charset=UTF-8"', $html);
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
	
	protected function clean($string) {
		return trim(str_replace(array('&nbsp;', '&Acirc;'), '', htmlentities($string, ENT_NOQUOTES, 'UTF-8')));
	}
	
	protected function decode($string) {
		$string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
		return preg_replace('/\s+/', ' ', $string);
	}
	
	public function handleError($message, array $context = []) {
		$context = array_map(
			function($element) {
				if (is_object($element)) {
					return $element->__toString();
				} elseif (is_array($element)) {	
					return implode(', ', $element);
				} else {
					return $element;
				}
			},
			$context
		);
		\Log::error($message, $context);
	}
	
	public function validUrl($url) {
		return  (preg_match($this->pattern, $url)) && 
				(filter_var($url, FILTER_VALIDATE_URL) !== false);
	}
	
	public function mergeUrl($base_url, $relative_url) {
		$components = parse_url($relative_url);
		if (array_key_exists('path', $components) && array_key_exists('query', $components)) {
			$path = $base_url->getPath();
			$path[2] = $components['path']; 
			$merged_url = $base_url->setPath($path)->setQuery($components['query'])->__toString();
		} else {
			$merged_url = null;
		}
		unset($components);
		return $merged_url;
	}
}
