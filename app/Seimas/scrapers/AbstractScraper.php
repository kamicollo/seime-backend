<?php

namespace Seimas\scrapers;
use Seimas\http\HttpClientInterface;
use Seimas\parsers\AbstractParser;
use Seimas\DOM\DOMXPath, Seimas\DOM\DOMDocument, Seimas\DOM\DOMElement;
use Log as Log;

abstract class AbstractScraper {
	/** @var string */
	const BaseURL = 'http://www3.lrs.lt/pls/inter/';
	/** @var Seimas\HttpClientInterface */
	protected $http_client;
	
	
	public function __construct(HttpClientInterface $http_client) {
		$this->http_client = $http_client;
	}
	
	public function scrapeMany(\Generator $data, \Closure $factory, AbstractParser $parser) {
		$this->http_client->startAsync();
		$unique_urls = new \ArrayObject();
		foreach($data as $databag) { /* @var $databag \Seimas\DataBag */
			//instantiate the object
			$object = $factory($databag->getClassName(), $databag->getInitData());
			//add url to request list if it is valid
			if ($this->validUrl($databag->getUrl())) {				
				$params = new \ArrayObject(
					[
					'object' => $object,
					'url' => $databag->getUrl(),
					'dom' => $databag->getDomxpath(),
					'parser' => $parser
					],
					\ArrayObject::ARRAY_AS_PROPS
				);
				$unique_urls[$databag->getUrl()] = $params;
			} elseif ($databag->getDomxpath() !== null) {
			// if not a valid url, but contains something for parsing - just parse right away
				$parser->parse($object, null, $databag->getDomxpath());
			} else {
				Log::error('Could not parse given URL', ['databag' => $databag]);
			}
		}
		foreach ($unique_urls as $url => $params) {
			$this->http_client->addAsync($url, $params);
		}
		//set http async callbacks
		$this->http_client->setAsyncCallbacks(
			function($html, $url, $params) {
				$this->processResponse($html, $params);					
			},
			function($error, $url, $params) {
				Log::error(
					'Loading web page failed', 
					['context' => $error, 'params' => $params, 'url' => $url]
				);
			}
		);
		//run the show.
		$this->http_client->finishAsync();		
	}
			
	public function processResponse($html, \ArrayObject $params) {
		try {
			$xpath = $this->prepareDomDocument($html);
			$this->assignId($params->object, $params->url);
			$params->parser->parse($params->object, $xpath, $params->dom);
		} catch (\Exception $e) {
			Log::error(
				$e->getMessage(),
				['context' => $e, 'params' => $params, 'html' => $html]
			);
		}
	}
	
	protected function assignId($object, $url) {
		$matches = [];
		if (preg_match($this->pattern, $url, $matches) && isset($matches[1])) {
			$object->id = $matches[1];
		} elseif ($object->id === null && $object->incrementing === false) {
			throw new \LogicException('Processed url without a valid id!');
		}
	}
	/**
	 *
	 * @param string $raw_html
	 * @return \Seimas\DOM\DOMXPath
	 * @throws \Exception
	 */
	protected function prepareDomDocument($raw_html) {
		$dom = new DOMDocument('1.0', 'UTF-8');
		$success = $dom->loadDirtyHTML($raw_html, 'windows-1257');
		if (!$success) {
			$e = new \Exception('Could not load the document');
			$e->data = ['html' => $raw_html];
			throw $e;
		}
		$dom->replaceRelativeURLs(self::BaseURL);;
		return new DOMXPath($dom);
		/**
		 * @todo Figure out why laravel hides these warnings (it throws an ErrorException, and
		 * I have no idea where it is caught...
		 */
	}
	
	/**
	 * 
	 * @param string
	 * @return boolean
	 */
	public function validUrl($url) {
		return  (preg_match($this->pattern, $url)) && 
				(filter_var($url, FILTER_VALIDATE_URL) !== false);
	}
}
