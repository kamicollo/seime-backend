<?php

namespace Seimas;
use \Log;
use Seimas\DOM\DOMXPath;
use Seimas\DOM\DOMElement;

class SittingScraper extends AbstractScraper {
	protected $pattern = '#http:\/\/www3\.lrs\.lt\/pls\/inter\/w5_sale\.fakt_pos\?p_fakt_pos_id=(-?[0-9]+)#';
			
	protected function parse(DOMXPath $xpath, Sitting $sitting) {
		$this->setMetaData($xpath, $sitting);
		$sitting->setChildrenData($this->parseQuestionLinks($xpath, $sitting));
		$this->setParticipationData($sitting);
	}
	
	protected function setMetaData(DOMXPath $xpath, Sitting $sitting) {
		$title = trim($xpath->document->getElementsByTagName('title')->item(0)->nodeValue);		
		$matches = array();
		preg_match("/Seimo posėdis\s+Nr\.(\d+)\s+\((\d{4}-\d{2}-\d{2}), (.+)\)/u", $title, $matches);
		if (count($matches) == 4) {
			/* title parsing */
			$sitting->title = $title;
			$sitting->number = $matches[1];
			$sitting->date = new \DateTime($matches[2], new \DateTimeZone('Europe/Vilnius'));
			$sitting->type = trim($matches[3]);
			/* finding links to content */
			$sitting->protocol_url = $xpath->getHrefByLinkName('Protokolas');
			$sitting->transcript_url = $xpath->getHrefByLinkName('Stenograma');
			$sitting->recording_url = $xpath->getHrefByLinkName('Garso įrašas');
			/* finding link to participation data */
			$sitting->participation_url = $xpath->getHrefByLinkName('Lankomumas');
		} else {
			$this->handleError(
				'Could not parse sitting metadata (or its title)', 
				['sitting' => $sitting]
			);
		}
	}
		
	protected function parseQuestionLinks(DOMXPath $xpath, Sitting $sitting) {	
		$table_dom = $xpath->query("//table[contains(@class,'basic')]/tr[td]");
		if ($table_dom->length === 0) {
			Log::warning('No questions found at sitting!', ['object' => $sitting]);
		} else {
			for($i = 0; $i < $table_dom->length; $i++) {
				$row = $table_dom->item($i);
				$questionData = $this->parseQuestionDOM($row, $i + 1);				
				if (count($questionData) > 0) {	
					yield $questionData;
				}
			}
		}
	}
	
	protected function parseQuestionDOM(DOMElement $row, $row_number) {
		$cells = $row->getElementsbyTagName('td');	
		$questionData = new \ArrayIterator();
		if ($cells->length == 3) {
			$questionData['number'] = $row_number;
			$questionData['start_time'] = $this->clean($cells->item(0)->nodeValue);
			$questionData['original_number'] = $this->clean($cells->item(1)->nodeValue);
			$questionData['title'] = $this->clean($cells->item(2)->nodeValue);
			$link_dom = $cells->item(2)->getElementsByTagName('a');
			if (is_object($link_dom)) {
				$questionData['url'] = $link_dom->item(0)->getAttribute('href');
			} else {				
				Log::warning(
					'Sitting link not recognised', 
					['url' => $link_dom->nodeValue]
				);
			}
		} else {
			Log::warning(
				'Could not parse question metadata correctly',
				['object' => $sitting,
				'number' => $i]
			);
		}
		return $questionData;
	}
	
	protected function setParticipationData(Sitting $sitting) {
		$this->http_client->request(
			$sitting->participation_url,
			function($html) use ($sitting) {
				try {
					$dom = $this->prepareDomDocument($html);
					$xpath = new DOMXPath($dom);
					$this->parseParticipation($xpath, $sitting);
				} catch (\Exception $e) {
					$this->handleError(
						$e->getMessage(),
						['context' => $e, 'object' => $sitting]
					);
				}			
			},
			function($error) use ($sitting) {
				$this->handleError(
					'Loading sitting participation web page failed', 
					['context' => $error, 'object' => $sitting]
				);
			}
		);
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
	
	protected function getParticipationState(\DOMElement $element = null) {
		if ($element !== null) {
			$value = $this->clean($element->nodeValue);
			return (int) (!empty($value));
		}
	}
}
