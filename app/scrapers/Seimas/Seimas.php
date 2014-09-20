<?php

namespace Seimas;

class Seimas {

	/** @var \Seimas\ScraperFactory * */
	protected $scraperFactory;

	public function __construct(ScraperFactory $factory) {
		$this->scraperFactory = $factory;
	}

	public function initialise($url, $object, $recursive = false) {
		$scraper = $this->scraperFactory->resolveScraper($object);
		if ($scraper instanceof AbstractScraper) {
			$scraper->scrape($url, $object);
			if ($recursive) {
				$this->initialiseChildren($object, $recursive);
			}
		} else {
			\Log::error('Scraper not found', ['object' => $object]);
		}
	}

	public function initialiseChildren($object, $recursive) {
		if (($object instanceof ParentInterface) && ($object->hasChildrenData())) {
			$childScraper = $this->scraperFactory->getScraperByClass($object->getChildClass());
			if ($childScraper instanceof AbstractScraper) {
				$childScraper->scrapeMany(
					$object->getChildrenData(),
					function($params = null) use ($object) {
						return $object->createChild($params);
					}
				);
				if ($recursive) {
					//initialised children, let's go one level deeper
					foreach ($object->getChildren() as $child) {
						$this->initialiseChildren($child, $recursive);
					}
				}
			} else {
				\Log::warning('Scraper for class ' . get_class($object) . ' not found');
			}
		}
	}
	
	public function initialiseDeeper($object, $recursively) {
		if ($object instanceof Ancestor) {
			foreach($object->getDescendants() as $descendant) {
				$class = $descendant->getClass();
				$scraper = $this->scraperFactory->getScraperByClass($class);
				$factory = $this->objectFactory->getFactoryByClass($class);
				$data_generator = $descendant->getGenerator();
				$scraper->scrapeMany($data_generator, $factory)
			}
		}
	}
	
}
