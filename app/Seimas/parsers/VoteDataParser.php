<?php

namespace Seimas\parsers;
use Seimas\DOM\DOMXPath, Seimas\DOM\DOMElement;
use Seimas\models\Vote;
use Seimas\factories\MemberFactory;
use \Log as Log;

class VoteDataParser extends AbstractParser {
	
	public function parse($vote, DOMXPath $xpath = null, DOMElement $element = null) {
		if (!($vote instanceof Vote)) {
			throw new \InvalidArgumentException(
					__CLASS__ . ' only accepts Vote objects, ' 
					. get_class($vote) . ' given instead.'
			);
		} else {
			$this->parseVotingData($xpath, $vote);
			$noted_inconsistency = $this->isVotingDataConsistent($xpath);
			$this->crossCheckVotingData($vote, $noted_inconsistency);
		}
	}
	
	protected function isVotingDataConsistent(DOMXPath $xpath) {
		$error_string = 'neatitinka suminių rezultatų, įrašytų protokole.';
		return (mb_stripos(strip_tags($xpath->document->saveHTML()), $error_string) === false);
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
			$adds_up = (
				($abstain == $vote->total_counts[Vote::ABSTAIN]) &&
				($accept == $vote->total_counts[Vote::ACCEPT]) &&
				($reject == $vote->total_counts[Vote::REJECT])
			);
			if (!$adds_up && !$consistent_data) {
				Log::notice('Vote data does not add up to vote summary data in title (also confirmed by website)');
			} elseif (!$adds_up && $consistent_data) {
				Log::warning(
					'Vote data does not add up to vote summary data in title and website does not note it!', 
					['object' => $vote, 'counts' => [$accept, $reject, $abstain]]
				);
			} elseif ($adds_up && !$consistent_data) {
				Log::warning(
					'Website states there is an error in vote data, but votes do add up!', 
					['object' => $vote, 'counts' => [$accept, $reject, $abstain]]
				);
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
			$data['fraction'] = $nodes->item(1)->getTrimmedValue();
			$data['vote'] = $this->parseVote($nodes);
		}
		return [$member, $data];
	}
	
	protected function parseVote(\DOMNodeList $nodes) {
		if ($nodes->item(2)->getTrimmedValue() != '') {
			return Vote::ACCEPT;
		} elseif ($nodes->item(3)->getTrimmedValue() != '') {
			return Vote::REJECT;
		} elseif ($nodes->item(4)->getTrimmedValue() != '') {
			return Vote::ABSTAIN;
		} else {
			return Vote::NO_VOTE;
		}
	}
}
