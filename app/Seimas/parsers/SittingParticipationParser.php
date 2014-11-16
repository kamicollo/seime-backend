<?php

namespace Seimas\parsers;
use Seimas\DOM\DOMXPath, Seimas\DOM\DOMElement;
use Seimas\models\Sitting;
use Seimas\factories\MemberFactory;
use Seimas\models\Member;
use Log as Log;

class SittingParticipationParser extends AbstractParser {
	
	public function parse($sitting, DOMXPath $xpath = null, DOMElement $element = null) {
		if (!($sitting instanceof Sitting)) {
			throw new \InvalidArgumentException(
					__CLASS__ . ' only accepts Sitting objects, ' 
					. get_class($sitting) . ' given instead.'
			);
		} else {
			$this->parseParticipation($xpath, $sitting);
		}
	}
	
	protected function parseParticipation(DOMXPath $xpath, Sitting $sitting) {
		$members_dom = $xpath->query("//table[contains(@cellpadding, '1')]//table[contains(@width, '100%')]/tr");
		$sitting->participants = new \SplObjectStorage();
		foreach ($members_dom as $member_dom) {
			$member = MemberFactory::createFromAnchor($member_dom->getElementsByTagName('a')->item(0));
			$state = $this->getParticipationState($member_dom->getElementsByTagName('td')->item(0));
			if (($state !== null) && ($member instanceof Member)) {
					$sitting->participants->attach($member, $state);
			} else {
				Log::warning(
					'Could not parse sitting participation data',
					['object' => $sitting, 'element' => $member_dom]
				);
			}
		}
	}
	
	protected function getParticipationState(DOMElement $element = null) {
		if ($element !== null) {
			$value = $element->getTrimmedValue();
			return (int) ($value == '+');
		}
	}
}
