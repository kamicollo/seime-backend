<?php

namespace Seimas;
use \Log;
use Seimas\DOM\DOMElement, Seimas\DOM\DOMXPath;

class RegistrationScraper extends AbstractScraper {
	protected $pattern = '#http:\/\/www3\.lrs\.lt\/pls\/inter\/w5_sale\.reg\?p_reg_id=-?[0-9]+#';
	
	public function parse(DOMElement $element, Registration $object) {
		$object->total_participants = $this->parseParticipantCount($object->title);
		$object->url = $this->parseRegistrationLink($element);
		if ($object->url !== null) {
			$this->setRegistrationData($object);
		} else {
			Log::warning(
				'could not find registration action url',
				['object' => $object, 'element' => $element]
			);
		}		
	}
	
	protected function parseParticipantCount($title) {
		$matches = array();
		preg_match('/užsiregistravo.\s*(\d+)/u', $title, $matches);
		if (isset($matches[1])) {
			return $matches[1];
		} else {
			return 0;
		}
	}
	
	protected function parseRegistrationLink(DOMElement $element) {
		$reg_link = $element->getElementsByTagName('td')->item(1)->getElementsByTagName('a')->item(0);
		if ($reg_link instanceof DOMElement) {
			 return $reg_link->getAttribute('href');	
		}
	}
	
	protected function setRegistrationData(Registration $object) {
		parent::scrape(
			$object->url,
			$object,
			function ($html, $url, $object) {
				try {
					$xpath = new DOMXPath($this->prepareDomDocument($html));
					$this->parseRegistrationData($xpath, $object);
					$this->crossCheckRegistrationData($object);
				} catch (\Exception $e) {
					$this->handleError(
						$e->getMessage(),
						['context' => $e, 'object' => $object, 'url' => $url]
					);
				}	
			}
		);
	}
	
	protected function parseRegistrationData(DOMXPath $xpath, Registration $registration) {
		$registration->registration_data = new \SplObjectStorage();				
		$reg_dom = $xpath->query("//table[contains(@cellpadding, '1')]//table[contains(@width, '100%')]/tr");
		foreach ($reg_dom as $member_data) {
			list($member, $data) = $this->parseRegistrationRow($member_data->getElementsByTagName('td'));
			if (($member !== null) && (array_key_exists('presence', $data))) {
				$registration->registration_data->attach($member, $data);
			} else {
				Log::warning('Could not parse Registration data', ['object' => $registration]);
			}
		}
	}
	
	protected function parseRegistrationRow(\DOMNodeList $nodes) {
		$member = null;
		$data = [];
		if ($nodes->length === 2) {
			$member = MemberFactory::createFromAnchor($nodes->item(1)->getElementsByTagName('a')->item(0));
			$data['presence'] = (int) ('+' == $this->clean($nodes->item(0)->nodeValue));
		}
		return [$member, $data];
	}
	
	protected function crossCheckRegistrationData(Registration $registration) {
		if ($registration->registration_data->count() > 0) {
			$sum = 0;
			foreach ($registration->registration_data as $member) {
				if ($registration->registration_data->offsetGet($member)['presence'] === 1) {
					$sum++;
				}
			}
		}
		if ($sum != $registration->total_participants) {
			Log::warning(
				'Registration data does not match with summary count in title',
				['computed_count' => $sum, 'object' => $registration,]
			);
		}
	}
	
}
