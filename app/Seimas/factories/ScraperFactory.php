<?php

namespace Seimas\factories;
use Seimas\http\HttpClientInterface;
use Log;

class ScraperFactory {
	
	protected $map = [
		'Seimas\models\Seimas' => 'Seimas\scrapers\SeimasScraper',
		'Seimas\models\Cadency' => 'Seimas\scrapers\CadencyScraper',
		'Seimas\models\Session' => 'Seimas\scrapers\SessionScraper',
		'Seimas\models\Sitting' => 'Seimas\scrapers\SittingScraper',
		'Seimas\models\Question' => 'Seimas\scrapers\QuestionScraper',
		'Seimas\models\Action'	=> 'Seimas\scrapers\ActionScraper',
		'Seimas\models\Item' => 'Seimas\scrapers\ItemScraper',
		'Seimas\models\Presenter' => 'Seimas\scrapers\PresenterScraper',
		'SittingParticipation' => 'Seimas\scrapers\SittingParticipationScraper',
		'VoteData'	=> 'Seimas\scrapers\VoteDataScraper',
		'RegistrationData' => 'Seimas\scrapers\RegistrationDataScraper',
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
	
	public function resolve($object) {
		$class_name = get_class($object);
		return $this->resolveByClass($class_name);
	}
	
	public function resolveByClass($class_name) {
		if (array_key_exists($class_name, $this->map)) {
			$class = $this->map[$class_name];
			return new $class($this->httpClient);
		} 
	}
	
}