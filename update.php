<?php

/* Šiuo kodu galima atnaujinti Seime.lt duomenis. Kadangi duomenų surinkimas iš
 * http://lrs.lt gali ilgai užtrukti, rekomenduojame kodą paleidinėti iš komandinės
 * eilutės. Jei tokios galimybės neturite - jums tereikės išimti pirmąjį IF sakinį.
 *
 * Norint, kad kodas veiktų, reikia pateikti sesiją / pasirinkti, kad būtų atnaujinama
 * pagal duomenis iš DB. Žr. 55 - 75 kodo eilutes.
 */
 
if (PHP_SAPI === 'cli') {

	define('START_TIME', microtime(true)); 
	ini_set('memory_limit','2048M');
	set_time_limit(10000);
	mb_internal_encoding('UTF-8');
	error_reporting(E_ALL);
	
 	/* el. paštas, kuris naudojamas siunčiant pranešimus, žr. Updater::SendPictureEmail() */
	define('NOTIF_EMAIL',YOUR_EMAIL); 

	define('BASE_DIR', dir(__FILE__));
	/* Būtini failai */
	require_once BASE_DIR . 'classes/utilities.php';
	require_once BASE_DIR . 'classes/DB.php';
	require_once BASE_DIR . 'classes/Factory.php';
	require_once BASE_DIR . 'classes/abstractions.php';
	require_once BASE_DIR . 'classes/Sesija.php';
	require_once BASE_DIR . 'classes/Posedis.php';
	require_once BASE_DIR . 'classes/Question.php';
	require_once BASE_DIR . 'classes/Action.php';
	require_once BASE_DIR . 'classes/Updater.php';
	
	/* Papildomi failai - priklauso nuo konfigūracijos žemiau */
	require_once BASE_DIR . 'extensions/QuestionParticipation.php';
	require_once BASE_DIR . 'extensions/RegistrationLink.php';
	
	/* MySQL nustatymai */	
	$sql_host_db = 'mysql:dbname=YOUR_DB_NAME;host=YOUR_HOST';
	$sql_user = 'YOUR_USERNAME';
	$sql_pass = 'YOUR_PASSWORD';
	$sql_driver_options = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'');
	
	/* Objektų medyje naudojamos klasės */
	$allowed_types = array(
		'session' => 'Session',
		'sitting' => 'Sitting',
		'question' => 'QuestionParticipation',
		'action' => 'RegistrationLink'
	);

	/* Inicijuojami Factory (singleton) ir DB objektai */
	$Factory = Factory::getInstance(array($sql_host_db, $sql_user, $sql_pass, $sql_driver_options), $allowed_types);
	$db = new DB($dsn, $username, $password, $driver_options);
	
	$sessions = array(); //sesijų, kurių duomenis reikia atnaujinti / sukurti sąrąšas
	
	/* Kodas, jei duomenų bazėje nėra nei vienos sesijos:
		
		Surinkti konkrečios sesijos duomenis:	
		$sessions[] = $Factory->getObject('session', 'SESIJOS_URL'); 
		//SESIJOS_URL atrodo taip: http://www3.lrs.lt/pls/inter/w5_sale.ses_pos?p_ses_id=ID
		
		Sesijų URL galima rasti adresu http://www3.lrs.lt/pls/inter/w5_sale.kad_ses
		
	*/
	
	/* Kodas, jei duomenų bazėje jau yra visos sesijos:
	
		$c_id = $db->getVar('SELECT max(id) FROM sessions', array()); //sužinome naujausios sesijos ID
		$sessions[] = $Factory->getObject('session', '', $c_id); //pridedame ją prie atnaujintinų sąrašo
		
		/* Bandome gauti dabartinės Seimo kadencijos sesijų sąrašą ir iš ten gauti pirmą paminėtą sesijos ID Ū/
		preg_match('/w5_sale\.ses_pos\?p_ses_id=(\d+)/', file_get_contents('http://www3.lrs.lt/pls/inter/w5_sale.kad_ses'), $matched);
		if (isset($matched[1]) && ($matched[1] != $c_id)) { 
			//jei radome ID ir jis nelygus dabartinei sesijai, tai turėtų būti nauja sesija - pridedame
			$sessions[] = $Factory->getObject('session', 'http://www3.lrs.lt/pls/inter/' . $matched[0]);
		}
	
	*/

	foreach ($sessions as $session) {
	
		$u = new Updater($session);
		
		/* Surenkame sesijos posėdžių sąrašą ir viską išsaugome */
		$u->updateSittingList();
		$u->announce('Updated sitting list');
	
		/* Daugiausiai resursų reikalaujantis etapas: rekursiškai keliaujam per objektų medį,
	 * renkame visus duomenis ir viską saugome */
		$u->obtainData();
		$u->announce('Updated all data');
		
		/* MEMBERS lentelė atnaujinama / užpildoma duomenimis apie Seimo narius ir jų
		 frakcijas */
		$db->exec($u->getSQL('fractions'));
		$u->announce('Updated member list and fractions [SQL]');
	
		/* Seime.lt skaičiavimai: klausimai skaldomi į dalis ir apskaičiuojamas tikslus lankomumas */
		$u->estimateParticipation();
		$u->announce('Estimated participation');
	
		/* Nustatomi ryšiai tarp registracijų į balsavimus ir pačių balsavimų */
		$u->linkRegistrations();
		$u->announce('Established links between registrations and votings');	
	
		/* PARTICIPATION_DATA lentelė užpildoma oficialiais lankomumo duomenimis iš 
		 * SITTING_PARTICIPATION lentelės */
		$db->exec($u->getSQL('official_participation'));
		$u->announce('Filled in official participation data [SQL]');	
	
		/* PARTICIPATION_DATA lentelė užpildoma posėdžių trukmėmis, apskaičiuotomis 
		 * pagal SUBQUESTIONS_PARTICIPATION lentelės duomenis */
		$db->exec($u->getSQL('available_hours'));
		$u->announce('Filled in available hours data [SQL]');	
	
		/* PARTICIPATION_DATA lentelė užpildoma tiksliais lankomumo duomenimis 
		 * pagal SUBQUESTIONS_PARTICIPATION lentelės duomenis */
		$db->exec($u->getSQL('participated_hours'));
		$u->announce('Filled in participated hours data [SQL]');	
		
		/* VOTES lentelė papildoma duomenimis apie tokius atvejus, kai Seimo narys
		 * užsiregistravo balsavimui, tačiau jame nesudalyvavo */ 
		$db->exec($u->getSQL('empty_registrations'));
		$u->announce('Updated empty registrations data [SQL]');
			
	}
	
	/* Patikrinama, ar nėra naujų Seimo narių - jei taip, gaunami jų vardai ir nuotraukos */
	$new = $db->getArray('SELECT id FROM members WHERE name = ?', array(''));
	if (!empty($new)) {
		$u->announce('Found new members! ' . json_encode($new));
		$new = $u->updateMembers($new);
		$db->insertMany('members', $new, array('id'));
		$u->announce('Added new members to the list!');
	}
	
	/* Atnaujinami duomenys apie tai, kurie Seimo nariai vėlai pradėjo kadenciją 
	 * ar kurie ją anksti baigė */
	$list = $u->getTermDetails();
	if (is_array($list)) {
		foreach($list as $member) {
			$db->getVar('UPDATE members SET cadency_start = ?, cadency_end = ? WHERE id = ?',
				array($member['cadency_start'], $member['cadency_end'], $member['id']));
		}
		$u->announce('Updated term details');
	}
	else $u->announce('Updating term details failed!');
	
	//VISKAS!
	$u->announce('DONE');
	
}
else {
	echo 'Access Denied';
}
