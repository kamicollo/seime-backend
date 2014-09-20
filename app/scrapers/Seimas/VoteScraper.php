<?php

namespace Seimas;
use \Log as Log;
use Seimas\DOM\DOMElement, Seimas\DOM\DOMXPath;

class VoteScraper extends AbstractScraper {
	protected $pattern = '#http:\/\/www3\.lrs\.lt\/pls\/inter\/w5_sale\.bals\?p_bals_id=(-?[0-9]+)#';
		
	public function parse(DOMElement $element, Vote $object) {
		$object->url = $this->parseVotingLink($element);
		$this->parseVotingTitle($object);
		if ($object->url !== null) {
			$this->setVotingData($object);
			//todo: cross-check voting summary vs. voting data, note if page notes that there's an error
		} else {
			Log::warning(
				'could not find voting action url',
				['object' => $object, 'element' => $element]
			);
		}
	}
	
	protected function parseVotingTitle(Vote $vote) {
		$title = str_replace(html_entity_decode('&nbsp;'), ' ', $vote->title);
		$matches = [];
		$pattern = '/Įvyko\s+balsavimas';
		$pattern .= '\s+(.+?)';
		$pattern .= ';?\s*(n?e?pritarta)?\s*';
		$pattern .= '\(už\s+([0-9]+),\s+prieš\s+([0-9]+),\s+susilaikė\s+([0-9]+)/';
		if (preg_match($pattern, $title, $matches)) {
			$vote->voting_topic = $matches[1];
			if ($matches[2] == 'pritarta') {
				$vote->voting_outcome = ACTION::VOTE_OUTCOME_ACCEPT;
			} elseif ($matches[2] == 'nepritarta') {
				$vote->voting_outcome = ACTION::VOTE_OUTCOME_REJECT;
			} else {
				$vote->voting_outcome = ACTION::VOTE_OUTCOME_UNKNOWN;
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
	
	protected function setVotingData(Vote $object) {
		parent::scrape(
			$object->url,
			$object,
			function ($html, $url, $object) {
				try {
					$xpath = new DOMXPath($this->prepareDomDocument($html));
					$this->parseVotingData($xpath, $object);
					$noted_inconsistency = $this->isVotingDataConsistent($xpath);
					$this->crossCheckVotingData($object, $noted_inconsistency);
				} catch (\Exception $e) {
					$this->handleError(
						$e->getMessage(),
						['context' => $e, 'object' => $object, 'url' => $url]
					);
				}	
			}
		);
	}
	
	protected function isVotingDataConsistent(DOMXPath $xpath) {
		$error_string = 'Individualūs balsavimo rezultatai, gauti elektroninėmis priemonėmis, neatitinka suminių rezultatų, įrašytų protokole.';
		return (mb_stripos($xpath->document->saveHTML(), $error_string) === false);
	}
	
	protected function crossCheckVotingData(Vote $vote, $consistent_data) {
		if (($vote->vote_data->count() > 0) && (is_array($vote->total_counts))) {
			$accept = 0;
			$reject = 0;
			$abstain = 0;
			foreach ($vote->vote_data as $member) {
				$data = $vote->vote_data->offsetGet($member);
				$accept = $accept + (int) ($data['vote'] === Vote::ACCEPT);
				$reject = $reject + (int) ($data['vote'] === Vote::REJECT);
				$abstain = $abstain + (int) ($data['vote'] === Vote::ABSTAIN);
			}
			if (
				(($abstain != $vote->total_counts[Vote::ABSTAIN]) ||
				($accept != $vote->total_counts[Vote::ACCEPT]) ||
				($reject != $vote->total_counts[Vote::REJECT])
				)
				&& (!$consistent_data)
			) {
				Log::warning(
					'Vote data does not add up to vote summary data in title and website does not note it!', 
					['object' => $vote, 'counts' => [$accept, $reject, $abstain]]
				);
			} elseif (!$consistent_data) {
				Log::warning(
					'Website states there is an error in vote data, but votes do add up!', 
					['object' => $vote, 'counts' => [$accept, $reject, $abstain]]
				);
			} elseif (
				($abstain != $vote->total_counts[Vote::ABSTAIN]) ||
				($accept != $vote->total_counts[Vote::ACCEPT]) ||
				($reject != $vote->total_counts[Vote::REJECT])
			) {
				Log::notice('Vote data does not add up to vote summary data in title (also confirmed by website)');
			}
		}
	}
	
	protected function parseVotingData(DOMXPath $xpath, Vote $vote) {
		$vote->vote_data = new \SplObjectStorage();				
		$voting_dom = $xpath->query("//table[contains(@class, 'basic')]/tr[td]");
		foreach ($voting_dom as $member_data) {
			list($member, $data) = $this->parseVoteRow($member_data->getElementsByTagName('td'));
			if (($member !== null) && (count($data) !== 0)) {
				$vote->vote_data->attach($member, $data);
			} else {
				Log::warning('Could not parse Vote voting data', ['object' => $vote]);
			}
		}
	}
	
	protected function parseVoteRow(\DOMNodeList $nodes) {
		$member = null;
		$data = [];
		if ($nodes->length === 5) {
			$member = MemberFactory::createFromAnchor($nodes->item(0)->getElementsByTagName('a')->item(0));
			$data['fraction'] = $this->clean($nodes->item(1)->nodeValue);
			$data['vote'] = $this->parseVote($nodes);
		}
		return [$member, $data];
	}
	
	protected function parseVote(\DOMNodeList $nodes) {
		if ($this->clean($nodes->item(2)->nodeValue) != '') {
			return Vote::ACCEPT;
		} elseif ($this->clean($nodes->item(3)->nodeValue) != '') {
			return Vote::REJECT;
		} elseif ($this->clean($nodes->item(4)->nodeValue) != '') {
			return Vote::ABSTAIN;
		} else {
			return Vote::NO_VOTE;
		}
	}
		
}
