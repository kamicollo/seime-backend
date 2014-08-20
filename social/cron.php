<html>
<head>
<meta charset="UTF-8">
</head>
<body>
<?php
	
	function initialize() {
		require_once '/home/aurimas/domains/seime.lt/public_html/includes/includes.php';
		define('BASE_DIR','/home/aurimas/domains/lplius.lt/public_html/seime.lt-backend/');
		setlocale(LC_TIME, 'lt_LT.UTF8');
		mb_internal_encoding('UTF-8');

		require_once BASE_DIR . 'classes/utilities.php';
		require_once BASE_DIR . 'classes/Factory.php';
		require_once BASE_DIR . 'classes/abstractions.php';
		require_once BASE_DIR . 'classes/Sesija.php';
		require_once BASE_DIR . 'classes/Posedis.php';
		require_once BASE_DIR . 'classes/Question.php';
		require_once BASE_DIR . 'classes/Action.php';
		require_once BASE_DIR . 'extensions/QuestionParticipation.php';
		require_once BASE_DIR . 'extensions/RegistrationLink.php';
		require_once BASE_DIR . 'extensions/SittingStats.php';
		require_once BASE_DIR . 'extensions/QuestionStats.php';	
		require_once BASE_DIR . 'classes/Updater.php';
		require_once '/home/aurimas/domains/seime.lt/public_html/includes/handler_functions.php';	
		
		Initialisator::initialise();
		Initialisator::$settings['mysql']['username'] = 'aurimas';
		Initialisator::$settings['mysql']['password'] = 'windows1257';
		$db_params = Initialisator::$settings['mysql'];
		$sql_params = array('mysql:dbname=' . $db_params['db'] . ';host=' . $db_params['host'] . '', $db_params['username'],  $db_params['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
		$allowed_types = array('session' => 'Session', 'sitting' => 'SittingStats', 'question' => 'QuestionStats', 'action' => 'RegistrationLink');
		$db = Initialisator::getDB();
		if (!($db instanceof DB))  throw new Exception('Database error');
		return array($db, Factory::getInstance($sql_params, $allowed_types));
	}
	
	function get_not_posted_sittings($db) {
		return $db->getArray("
			SELECT id FROM sittings 
			WHERE NOT EXISTS (SELECT 1 FROM posted_statistics WHERE id = sittings_id) 
			AND end_time <> '0000-00-00 00:00:00' 
			ORDER BY id DESC
			LIMIT 5", array());
	}
	
	function initialize_curl() {
		$ch = curl_init();
		$secret = md5('BaltasisAnciuvis' . date('Y-m-d H'));
		curl_setopt($ch, CURLOPT_URL,'http://trumpai.seime.lt/wp-content/plugins/remote-publishing/post.php?secret=' . $secret);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		return $ch;
	}
	
	function get_template_dom(Sitting $sitting, Session $session) {
		ob_start();
		include(dirname(__FILE__) . '/template.php');
		$html = ob_get_clean();
		$dom = new DOMDocument('1.0', 'UTF-8');
		$html = mb_convert_encoding($html, 'HTML-ENTITIES', "UTF-8");
		$dom->loadHTML($html);
		return $dom;
	}
	
	function get_mentioned_members(DOMDocument $dom) {
		$xpath = new DOMXPath($dom);
		$members = array();
		$members_results = $xpath->query("//a[contains(@href,'nariai')]");
		foreach($members_results as $m) { if (!in_array($m->nodeValue, $members)) $members[] = $m->nodeValue;	}
		return $members;
	}
	
	function get_tweets(Sitting $sitting) {
		$tweets[] = sprintf('Posėdis truko %1$s (įskaitant pertraukas), jame pasisakė %2$s Seimo %3$s. Balsuota %4$s %5$s (%6$s)',
			$sitting->getLength(),
			$sitting->getMemberStats('speakers'),
			__ending($sitting->getMemberStats('speakers')),
			$sitting->getVotings('all'),
			__ending($sitting->getVotings('all'), array('kartų', 'kartą', 'kartus')),
			date("Y-m-d",strtotime($sitting->getEndTime()))
		);
		$tweets[] = sprintf('Visuose posėdžio balsavimuose dalyvavo %1$s Seimo %2$s. Mažiau nei 30%% laiko posėdyje buvo %3$s Seimo %4$s (%5$s)',
			$sitting->getMemberStats('full-attendance'),
			__ending($sitting->getMemberStats('full-attendance')),
			$sitting->getMemberStats('short-attendance'),
			__ending($sitting->getMemberStats('short-attendance')),
			date("Y-m-d",strtotime($sitting->getEndTime()))			
		);
		$tweets[] = sprintf('Oficialiai posėdyje dalyvavo %1$s Seimo %2$s. Seime.lt duomenimis, Seimo nariai posėdyje buvo %3$s%% laiko (%4$s)',
			$sitting->participation('participated'),
			__ending($sitting->participation('participated')),
			$sitting->participation('time-based'),
			date("Y-m-d",strtotime($sitting->getEndTime()))			
		);
		return $tweets;
	}
	
	function get_fb_text(DOMDocument $dom, Sitting $sitting) {
		$text = array();
		$xpath = new DOMXpath($dom);
		$results = $xpath->query('//ul[@id="general-info"]/li');
		foreach($results as $r) { $text[] = DOMInnerHTML($r); }
		array_pop($text);		
		$text = implode(" ", $text);
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		return str_replace(array('<a', '<em', 'a>'), array(' <a', ' <em', 'a> '), $text); //fix for eaten spaces by DOMInnerHTML monster
	}

	try {		
		list($db, $Factory) = initialize();
		$sittings = get_not_posted_sittings($db);							
		if ($sittings !== array()) {
			$ch = initialize_curl();
			foreach ($sittings as $a) { //generate html files for each of the sittings
				$sitting = $Factory->getObject('sitting', '', (int) $a['id']);
				$session = $Factory->getObject('session', '', $sitting->getSessionID('getId'));
				$dom = get_template_dom($sitting, $session);				
				$data = array(
					'title' => $sitting->getTitle(),
					'slug' => $sitting->getTitle(),
					'category_name' => $session->getNumber() . ' ' . $session->getType() . ' sesija',
					'tags' => get_mentioned_members($dom),
					'date' => strtotime($sitting->getEndTime()),
					'tweets' => get_tweets($sitting),
					'facebook' => strip_tags(get_fb_text($dom, $sitting)),					
					'text' => html_entity_decode(DOMInnerHTML($dom->getElementsByTagName('body')->item(0)), ENT_NOQUOTES, 'UTF-8'),				
				);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
				$c = '';
				$c = curl_exec($ch);
				//save the success
				if ($c == 'success') {
					$db->getVar('INSERT INTO posted_statistics VALUES (?, 1) ON DUPLICATE KEY UPDATE posted = 1', array($sitting->getId() ));
					echo "prideta " . $sitting->getId() . "<br>";
				}
				else { echo $c; print_f($data);	}
			}
			curl_close($ch);
		}
		else { echo "no new sittings"; }
	}
	catch(Exception $e) { print_f($e->__toString()); }