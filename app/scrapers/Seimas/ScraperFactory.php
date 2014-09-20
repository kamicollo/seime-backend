<?php

namespace Seimas;

class ScraperFactory {
	
	protected $map = [
		'Seimas\Session' => 'Seimas\SessionScraper',
		'Seimas\Sitting' => 'Seimas\SittingScraper',
		'Seimas\Question' => 'Seimas\QuestionScraper',
		'Seimas\Action'	=> 'Seimas\ActionScraper'
	];
	
	/** @var \Seimas\HttpClientInterface */
	protected $httpClient = null;
	
	public function __construct(HttpClientInterface $httpClient, array $map = []) {
		$this->map = array_merge($this->map, $map);
		$this->httpClient = $httpClient;
	}
	
	public function setHttpClient(HttpClientInterface $httpClient) {
		$this->httpClient = $httpClient;
	}
	
	public function resolveScraper($object) {
		$class_name = get_class($object);
		return $this->getScraperByClass($class_name);
	}
	
	public function getScraperByClass($class_name) {
		if (array_key_exists($class_name, $this->map)) {
			$class = $this->map[$class_name];
			return new $class($this->httpClient);
		} else {
			\Log::warning($class_name);
		}
	}
	
}