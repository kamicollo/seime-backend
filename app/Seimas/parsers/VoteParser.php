<?php

namespace Seimas\parsers;
use Seimas\DOM\DOMXPath, Seimas\DOM\DOMElement;
use Seimas\models\Vote, Seimas\models\Action;
use \Log as Log;

class VoteParser extends AbstractParser {
	
	public function parse($vote, DOMXPath $xpath = null, DOMElement $element = null) {
		if (!($vote instanceof Vote)) {
			throw new \InvalidArgumentException(
					__CLASS__ . ' only accepts Vote objects, ' 
					. get_class($vote) . ' given instead.'
			);
		} else {
			$this->parseData($vote, $element);
		}
	}
	
	protected function parseData(Vote $vote, DOMElement $element) {
		$vote->url = $this->parseVotingLink($element);
		$this->parseVotingTitle($vote);
		if ($vote->url !== null) {
			$vote->setVoteData($this->getDataBag($vote->url));
		} else {
			Log::warning(
				'could not find voting action url',
				['object' => $vote, 'element' => $element]
			);
		}
	}
	
	protected function getDataBag($url) {
		yield new \Seimas\DataBag(null, $url);
	}
	
	protected function parseVotingTitle(Vote $vote) {
		$title = str_replace(html_entity_decode('&nbsp;'), ' ', $vote->title);
		$matches = [];
		$pattern = '/Įvyko\s+balsavimas';
		$pattern .= '\s+(.+?)?';
		$pattern .= ';?\s*(n?e?pritarta)?\s*';
		$pattern .= '\(už\s*([0-9]+),\s*prieš\s*([0-9]+),\s*susilaikė\s*([0-9]+)';
		$pattern .= '/';
		if (preg_match($pattern, $title, $matches)) {
			$vote->voting_topic = $matches[1];
			if ($matches[2] == 'pritarta') {
				$vote->outcome = ACTION::VOTE_OUTCOME_ACCEPT;
			} elseif ($matches[2] == 'nepritarta') {
				$vote->outcome = ACTION::VOTE_OUTCOME_REJECT;
			} else {
				$vote->outcome = ACTION::VOTE_OUTCOME_UNKNOWN;
			}
			$vote->total_counts = [
				VOTE::ACCEPT => $matches[3],
				VOTE::REJECT => $matches[4],
				VOTE::ABSTAIN => $matches[5]
			];
		} else {
			Log::warning(
				'could not parse vote title',
				['title' => $title, 'object' => $vote]
			);
		}
	}

	
	protected function parseVotingLink(DOMElement $element) {
		$voting_anchor = $element->getElementsByTagName('td')->item(1)->getElementsByTagName('a')->item(0);
		if ($voting_anchor instanceof DOMElement) {
			return $voting_anchor->getAttribute('href');							
		}
	}
}
