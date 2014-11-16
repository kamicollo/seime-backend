<?php

namespace Seimas\parsers;
use Seimas\DOM\DOMXPath, Seimas\DOM\DOMElement;
use Seimas\models\UnanimousVote as Action;
use \Log as Log;

class UnanimousVoteParser extends AbstractParser {
	
	public function parse($vote, DOMXPath $xpath = null, DOMElement $element = null) {
		if (!($vote instanceof Action)) {
			throw new \InvalidArgumentException(
					__CLASS__ . ' only accepts Unanimous Vote objects, ' 
					. get_class($vote) . ' given instead.'
			);
		} elseif (stripos($vote->title, 'pritarta') !== false) {
				$vote->outcome = Action::VOTE_OUTCOME_ACCEPT;
		} else {
				$vote->outcome = Action::VOTE_OUTCOME_REJECT;
		}
	}
	
}
