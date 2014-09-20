<?php

namespace Seimas;
use \Log as Log;
use Seimas\DOM\DOMXPath, Seimas\DOM\DOMElement;

class QuestionScraper extends AbstractScraper {
	protected $pattern = '#http:\/\/www3\.lrs\.lt\/pls\/inter\/w5_sale\.klaus_stadija\?p_svarst_kl_stad_id=(-?[0-9]+)#';
		
	public function scrapeMany(\Generator $urls, \Closure $childfactory, \Closure $cb = null, \Closure $error_cb = null) {
		$this->http_client->startAsync();
		foreach ($urls as $question_data) {
			$child = $this->initQuestion($childfactory, $question_data);
			if ($this->validUrl($child->url)) {
				$this->http_client->addAsync($child->url, $child);
			}
		}
		$this->http_client->setAsyncCallbacks($this->getDefaultCallback(), $this->getDefaultErrorCallback());
		$this->http_client->finishAsync();		
	}
	
	protected function initQuestion(\Closure $factory, \ArrayAccess $child_data) {
			$child = $factory();
			$child->url = $child_data['url'];
			$child->title = $child_data['title'];
			$child->start_time = $child_data['start_time'];
			$child->number = $child_data['number'];
			$child->original_number = $child_data['original_number'];
			return $child;
	}
		
	protected function parse(DOMXPath $xpath, Question $question) {
		$question->setItemData($this->parseItems($xpath));
		$question->setChildrenData($this->parseActions($xpath));
	}
	
	protected function parseActions(DOMXPath $xpath) {
		$actions_dom = $xpath->query("//table[contains(@class, 'basic')]/tr[td]");
		for ($i = 0; $i < $actions_dom->length; $i++) {
			$action_element = $actions_dom->item($i);
			yield [
				'number' => $i,
				'dom_object' => $action_element
			];
		}
	}
	
	protected function parseItems(DOMXPath $xpath) {
		$inner_questions = $xpath->query("//li[preceding::h4 and following::h4]");
		if ($inner_questions->length > 0) {
			foreach($inner_questions as $context) {
				yield ['dom' => $xpath, 'context' => $context];
			}
		} else  {
			yield ['dom' => $xpath, 'context' => null];
		}
	}
	
	
	
	
	
}
