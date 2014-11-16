<?php

namespace Seimas\parsers;
use Seimas\DOM\DOMXPath, Seimas\DOM\DOMElement;
use Seimas\models\Registration;
use Seimas\factories\MemberFactory;
use \Log as Log;

class RegistrationDataParser extends AbstractParser {
	
	public function parse($registration, DOMXPath $xpath = null, DOMElement $element = null) {
		if (!($registration instanceof Registration)) {
			throw new \InvalidArgumentException(
					__CLASS__ . ' only accepts Registration objects, ' 
					. get_class($registration) . ' given instead.'
			);
		} else {
			$this->parseRegistrationData($xpath, $registration);
			$this->crossCheckRegistrationData($registration);
		}
	}
	
	protected function parseRegistrationData(DOMXPath $xpath, Registration $registration) {
		$registration->registration_data = new \SplObjectStorage();				
		$reg_dom = $xpath->query("//table[contains(@cellpadding, '1')]//table[contains(@width, '100%')]/tr");
		foreach ($reg_dom as $member_data) {
			list($member, $data) = $this->parseRegistrationRow($member_data->getElementsByTagName('td'));
			if (($member !== null) && ($data !== null)) {
				$registration->registration_data->attach($member, $data);
			} else {
				Log::warning('Could not parse Registration data', ['object' => $registration]);
			}
		}
	}
	
	protected function parseRegistrationRow(\DOMNodeList $nodes) {
		$member = null;
		$data = null;
		if ($nodes->length === 2) {
			$member = MemberFactory::createFromAnchor($nodes->item(1)->getElementsByTagName('a')->item(0));
			$data = (int) ('+' === $nodes->item(0)->getTrimmedValue());
		}
		return [$member, $data];
	}
	
	protected function crossCheckRegistrationData(Registration $registration) {
		if ($registration->registration_data->count() > 0) {
			$sum = 0;
			foreach ($registration->registration_data as $member) {
				if ($registration->registration_data->offsetGet($member) === 1) {
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
