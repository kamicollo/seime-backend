<?php

namespace Seimas;
use Seimas\models\AncestorInterface;
use Seimas\factories\ScraperFactory, Seimas\factories\ParserFactory;
use Seimas\scrapers\AbstractScraper;
use Seimas\parsers\AbstractParser;

class DataLoader {

	/** @var \Seimas\ScraperFactory * */
	protected $scraperFactory;

	public function __construct(ScraperFactory $s_factory, ParserFactory $p_factory) {
		$this->scraperFactory = $s_factory;
		$this->parserFactory = $p_factory;
	}
	
	public function initialise($url, $object, $recursive = false) {
		$scraper = $this->scraperFactory->resolve($object);
		$parser = $this->parserFactory->resolve($object);
		$object_factory = function() use ($object, $url) { 
			$object->url = $url;
			return $object;
		};
		$data = $this->createDummyGenerator($url);
		$this->init($data, $scraper, $parser, $object_factory, get_class($object));
		if ($recursive) {
			$this->initialiseDeeper($object, $recursive);
		}
	}
	
	protected function init(
			\Generator $data, 
			AbstractScraper $scraper = null,
			AbstractParser $parser = null,
			\Closure $factory = null
	) {
		if ($scraper === null) {
			\Log::error('Scraper not found', ['context' => func_get_args()]);
			return;
		}
		if ($parser === null) {
			\Log::error('Parser not found', ['context' => func_get_args()]);
			return;
		}
		if ($factory === null) {
			\Log::error('Factory not found', ['context' => func_get_args()]);
			return;
		}
		$scraper->scrapeMany($data, $factory, $parser);
	}
	
	public function initialiseDeeper($object, $recursive) {
		if ($object instanceof AncestorInterface) {
			//\Debugbar::info($object->url);
			foreach($object->getDescendantsData() as $descendants) {
				/* @var $descendants \Seimas\DescendantBag */
				if ($descendants instanceof DescendantBag) {
					$class = $descendants->getClass();
					$scraper = $this->scraperFactory->resolveByClass($class);
					$parser = $this->parserFactory->resolveByClass($class);
					$factory = $object->getDescendantFactory($class);
					$data_generator = $descendants->getGenerator();
					$this->init($data_generator, $scraper, $parser, $factory, $class);
				}
			}
			
			if ($recursive) {
				$this->initDescendantsDeeper($object);				
			}
		}
	}
	
	protected function initDescendantsDeeper($object) {
		foreach($object->getDescendants() as $generated_descendants) {
			foreach($generated_descendants as $descendant) {
				$this->initialiseDeeper($descendant, true);
			}
		}
	}
	
	protected function createDummyGenerator($url) {
		yield new DataBag('', $url);
	}
	
}
