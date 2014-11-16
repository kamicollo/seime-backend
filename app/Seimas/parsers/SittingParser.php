<?php

namespace Seimas\parsers;
use Seimas\DOM\DOMXPath, Seimas\DOM\DOMElement;
use Seimas\models\Sitting;
use Log as Log;

class SittingParser extends AbstractParser {
	
	public function parse($sitting, DOMXPath $xpath = null, DOMElement $element = null) {
		if (!($sitting instanceof Sitting)) {
			throw new \InvalidArgumentException(
					__CLASS__ . ' only accepts Sitting objects, ' 
					. get_class($sitting) . ' given instead.'
			);
		} else {
			$this->setMetaData($xpath, $sitting);
			$sitting->setChildrenData($this->parseQuestionLinks($xpath, $sitting));
			$sitting->setParticipationData($this->getParticipation($sitting));
		}
	}
	
	protected function setMetaData(DOMXPath $xpath, Sitting $sitting) {
		$title = trim($xpath->document->getElementsByTagName('title')->item(0)->nodeValue);		
		$matches = array();
		preg_match("/Seimo posėdis\s+Nr\.(\d+)\s+\((\d{4}-\d{2}-\d{2}), (.+)\)/u", $title, $matches);
		if (count($matches) == 4) {
			/* title parsing */
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
			Log::error(
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
					yield new \Seimas\DataBag(null, $questionData->url, $questionData);
				} else {
					Log::warning(
						'Could not parse question metadata correctly',
						['object' => $sitting,
						'number' => $i]
					);
				}
			}
		}
	}
	
	protected function getParticipation(Sitting $sitting) {
		if ($sitting->participation_url !== null) {
			yield new \Seimas\DataBag(null, $sitting->participation_url);
		}
	}
	 
	protected function parseQuestionDOM(DOMElement $row, $row_number) {
		$cells = $row->getElementsbyTagName('td');	
		$questionData = new \ArrayObject([], \ArrayObject::ARRAY_AS_PROPS);
		if ($cells->length == 3) {
			$questionData['number'] = $row_number;
			$questionData['start_time'] = $cells->item(0)->getTrimmedValue();
			$questionData['original_number'] = $cells->item(1)->getTrimmedValue();
			$questionData['title'] = $cells->item(2)->getTrimmedValue();
			$link_dom = $cells->item(2)->getElementsByTagName('a');
			if (is_object($link_dom)) {
				$questionData['url'] = $link_dom->item(0)->getAttribute('href');
			} else {				
				Log::warning(
					'Sitting link not recognised', 
					['url' => $link_dom->nodeValue]
				);
			}
		} 
		return $questionData;
	}

}
