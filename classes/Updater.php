<?php

class Updater {

	protected $session;
	protected $last_time = 0;

	public function __construct(Session $session) {
		$this->session = $session;
		$this->last_time = microtime(true);
		$this->start_time = $this->last_time;
	}
	
	/* Surenkame sesijos posėdžių sąrašą ir viską išsaugome */
	/* Scrape the list of the sittings in the session and save */
	public function updateSittingList() {
		$this->session->scrapeData(true);
		$this->session->saveData();
	}

	/* Daugiausiai resursų reikalaujantis etapas: rekursiškai keliaujam per objektų medį,
	 * renkame visus duomenis ir viską saugome */
	/* The heavylifting part:Do the recursive object-tree scraping and save all the obtained data */
	public function obtainData() {
		$this->session->initialise();
		$this->session->initialiseChildren(true);
	}
	
	/* Seime.lt skaičiavimai: klausimai skaldomi į dalis ir apskaičiuojamas tikslus lankomumas */
	/* Seime.lt estimations: Participation data is estimated precisely, at sub-question level */
	public function estimateParticipation() {
		foreach ($this->session->getChildren() as $sitting) {
			foreach ($sitting->getChildren() as $question) {
				if (false === $question->populateParticipation()) {
					$question->estimateParticipation();
					$question->saveParticipation();
				}
			}
		}	
	}
	
	/* Nustatomi ryšiai tarp registracijų į balsavimus ir pačių balsavimų */
	/* Establish links between registrations for voting and voting themselves */
	public function linkRegistrations() {
		foreach ($this->session->getChildren() as $sitting) {
			foreach ($sitting->getChildren() as $question) {
				foreach ($question->getChildren() as $action)
					$action->InitialiseLink();
			}
		}	
	}
	
	/* Pagalbinė funkcija, grąžinanti SQL užklausas iš aplanko 'sqls/'
	/* Helper function: returns SQL commands from files in 'sqls/' folder */
	public function getSQL($script) {
		$file = BASE_DIR . 'sqls/' . $script . '.sql';
		if (file_exists($file)) {
			return file_get_contents($file);
		}
		else throw new Exception('SQL file is unavailable: ' . $file);	
	}
	
	/* Pagalbinė funkcija, spausdinanti žinutę ir laiką nuo paskutinės žinutės */
	/* Helper function: prints message and elapsed execution time since last message */
	public function announce($message) {
		$c_time = microtime(true);
		echo 	$message . ' for session #' . $this->session->getId() . 
					' in ' . round(($c_time - $this->last_time), 3) . 's' . 
					' (total time: ' . round(($c_time - $this->start_time), 3) . 's)<br><br>';
		$this->last_time = $c_time;
		flush();
	}

	/* Seimo narių duomenų atnaujinimas - surenkamas aprašymas, vardas, nuotrauka. Išsiunčiamas pranešimas apie naują informaciją */
	/* Updates member info - scrapes description, get name & picture. Notifies via email if new info is added */
	public function updateMember(array $member) {
		$url = 'http://www3.lrs.lt/pls/inter/w5_show?p_r=8801&p_k=1&p_a=5&p_asm_id=' . $member['id']  .'&p_kade_id=7';
		if($html = @file_get_contents($url)) {
		//clean the HTML
		$html = ScrapingUtilities::cleanHTML($html);
		//parse the HTML
			$dom = new DOMDocument('1.0', 'UTF-8');
			@$dom->loadHTML($html);
		//get the name
			$name = $dom->getElementsByTagName('title')->item(0)->nodeValue;
			$name = str_replace('-', ' - ', $name);
			$member['name'] = trim(mb_convert_case($name, MB_CASE_TITLE));
		//get the image src & send email with details
			$div = $dom->getElementById('divDesContent');				
			$images = $div->getElementsByTagName('img')->item(0);
			if (is_object($images)) {
				$member['image_src'] = $images->getAttribute('src');
				$this->sendPictureEmail($member);
			}
		//return the updated data
			return $member;
		}
		else {
			throw new Exception('Remote file fetching failed');
		}	
	}
	
	/* Išsiunčiamas el. laiškas apie naują Seimo narį - naudojama Seime.lt svetainėje */
	/* Sends an email about new member added - for Seime.lt purposes */
	public function sendPictureEmail(array $member) {
		$subject = '[seime.lt] - naujas narys: '. $member['name'];
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
		$text = '	<strong>Pridėtas naujas seimo narys</strong><br>
							Nuotraukos URL: %1$s<br>
							Full dydžio nuotrauka (180x135): /images/people/full/%2$s.jpg<br/>
							Thumb (60x45): /images/people/thumbs/%2$s.jpg<br/></br/>
							Mekeke!';
		$text = sprintf($text, $member['image_src'], $member['id']);
		mail(NOTIF_EMAIL, $subject, wordwrap($text), $headers);	
	}
	
	/* Pagalbinis updateMember metodas */
	/* Wrapper for updateMember method */
	public function updateMembers(array $members) {
		foreach($members as &$member) {
			try {
				$member = $this->updateMember($member);
			}
			catch(Exception $e) {
				$this->announce("Updating data failed for $member: " . $e->__toString());
			}
		}
		return $members;			
	}
	
	/* Atnaujinama Seimo narių, kurie pradėjo kadenciją vėliau arba ją baigė per anksti, informacija */
	/* Update details on members who entered late of left early */
	public function getTermDetails() {
		$list = array();
		if ($html = @file_get_contents('http://www3.lrs.lt/pls/inter/w5_show?p_r=6113&p_k=1')) {
		//clean the HTML
			$html = ScrapingUtilities::cleanHTML($html);
		//parse the HTML
			$dom = new DOMDocument('1.0', 'UTF-8');
			@$dom->loadHTML($html);
			$xpath = new DOMXPath($dom);
			$r = $xpath->query('//td[a[contains(@href,"p_asm_id")] and (contains(., "iki") or contains(., "nuo"))]');
			if ($r instanceof DOMNodeList) {
				foreach ($r as $node) {
					preg_match('#p_asm_id=(\d+)#', DOMInnerHTML($node), $matches);
					$id = $matches[1];
					$start = '0000-00-00';
					$end = '0000-00-00';
					if (preg_match('#nuo (\d{4} \d{2} \d{2})#', DOMInnerHTML($node), $matches)) {
						$start = str_replace(' ', '-', $matches[1]);
					}
					if (preg_match('#iki (\d{4} \d{2} \d{2})#', DOMInnerHTML($node), $matches)) {
						$end = str_replace(' ', '-', $matches[1]);
					}
					$list[] = array('id' => $id, 'cadency_start' => $start, 'cadency_end' => $end);
				}
				return $list;
			}
			else {
				$this->announce('UPDATING TERM DETAILS FAILED - HTML not recognized!');
			}
		}
	}
				
}

