<?php

/* This code is meant to be used to update or collect voting and participation data 
 * from http://lrs.lt website. As the scraping process can take a long time, we
 * we recommend to run this script from the command line. If you do not have command
 * line access, you'll need to comment out the first IF statement to make the code run.
 *
 * Additionally, you need to provide configuration for MySQL (lines 42-45), 
 * email where notifications about new data will be sent (line 23) and
 * provide the session url or indicate that the update should use the DB data
 * (lines 60 - 80).
 */
 
if (PHP_SAPI === 'cli') {

	define('START_TIME', microtime(true)); 
	ini_set('memory_limit','2048M');
	set_time_limit(10000);
	mb_internal_encoding('UTF-8');
	error_reporting(E_ALL);
	
 	/* Email address for notifications, see Updater::SendPictureEmail() for details */
	define('NOTIF_EMAIL',YOUR_EMAIL); 

	define('BASE_DIR', dir(__FILE__));
	/* Required files */
	require_once BASE_DIR . 'classes/utilities.php';
	require_once BASE_DIR . 'classes/DB.php';
	require_once BASE_DIR . 'classes/Factory.php';
	require_once BASE_DIR . 'classes/abstractions.php';
	require_once BASE_DIR . 'classes/Sesija.php';
	require_once BASE_DIR . 'classes/Posedis.php';
	require_once BASE_DIR . 'classes/Question.php';
	require_once BASE_DIR . 'classes/Action.php';
	require_once BASE_DIR . 'classes/Updater.php';
	
	/* Optional files - depends on the object tree settings below */
	require_once BASE_DIR . 'extensions/QuestionParticipation.php';
	require_once BASE_DIR . 'extensions/RegistrationLink.php';
	
	/* MySQL settings */	
	$sql_host_db = 'mysql:dbname=YOUR_DB_NAME;host=YOUR_HOST';
	$sql_user = 'YOUR_USERNAME';
	$sql_pass = 'YOUR_PASSWORD';
	$sql_driver_options = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'');
	
	/* Classes used in the object tree */
	$allowed_types = array(
		'session' => 'Session',
		'sitting' => 'Sitting',
		'question' => 'QuestionParticipation',
		'action' => 'RegistrationLink'
	);

	/* Initialisation of the Factory (singleton) and DB objects */
	$Factory = Factory::getInstance(array($sql_host_db, $sql_user, $sql_pass, $sql_driver_options), $allowed_types);
	$db = new DB($dsn, $username, $password, $driver_options);
	
	$sessions = array(); //list of sessions that need to be updated
	
	/* Code to be used if the current DB does not contain data:
		
		Add a specific session to the update list:	
		$sessions[] = $Factory->getObject('session', 'SESIJOS_URL'); 
		//SESIJOS_URL looks like that: http://www3.lrs.lt/pls/inter/w5_sale.ses_pos?p_ses_id=ID
		
		All the session urls can be found at http://www3.lrs.lt/pls/inter/w5_sale.kad_ses
		
	*/
	
	/* Code to be used if only incremental data update is needed:
	
		$c_id = $db->getVar('SELECT max(id) FROM sessions', array()); //get the ID of the current / last session
		$sessions[] = $Factory->getObject('session', '', $c_id); //add it to the update list
		
		// Retrieve the list of all sessions and find the ID of the first session in the list
		preg_match('/w5_sale\.ses_pos\?p_ses_id=(\d+)/', file_get_contents('http://www3.lrs.lt/pls/inter/w5_sale.kad_ses'), $matched);
		if (isset($matched[1]) && ($matched[1] != $c_id)) { 
			//if we found an ID and its not equal to current ID - a new session has started, let's add it
			$sessions[] = $Factory->getObject('session', 'http://www3.lrs.lt/pls/inter/' . $matched[0]);
		}
	
	*/

	foreach ($sessions as $session) {
	
		$u = new Updater($session);
		
		/* Scrape the list of the sittings in the session and save */
		$u->updateSittingList();
		$u->announce('Updated sitting list');
	
		/* The heavylifting part:Do the recursive object-tree scraping and save all the obtained data */
		$u->obtainData();
		$u->announce('Updated all data');
		
		/* MEMBERS lentelė is updated / filled with data about parliament members and their fractions */
		$db->exec($u->getSQL('fractions'));
		$u->announce('Updated member list and fractions [SQL]');
	
		/* Seime.lt skaičiavimai: klausimai skaldomi į dalis ir apskaičiuojamas tikslus lankomumas */
		$u->estimateParticipation();
		$u->announce('Estimated participation');
	
		/* Seime.lt estimations: Participation data is estimated precisely, at sub-question level */
		$u->linkRegistrations();
		$u->announce('Established links between registrations and votings');	
	
		/* PARTICIPATION_DATA table is filled with official participation data from
		 * SITTING_PARTICIPATION table */
		$db->exec($u->getSQL('official_participation'));
		$u->announce('Filled in official participation data [SQL]');	
	
		/* PARTICIPATION_DATA table is filled with the lengths of sittings estimated 
		 * according to the data of SUBQUESTIONS_PARTICIPATION table */
		$db->exec($u->getSQL('available_hours'));
		$u->announce('Filled in available hours data [SQL]');	
	
		/* PARTICIPATION_DATA table is filled with the estimated precise participation 
		 * details according to the data of SUBQUESTIONS_PARTICIPATION table */
		$db->exec($u->getSQL('participated_hours'));
		$u->announce('Filled in participated hours data [SQL]');	
		
		/* VOTES table is updated with data on cases when a parliament member registered
		 * for a voting, but did not participate in it */ 
		$db->exec($u->getSQL('empty_registrations'));
		$u->announce('Updated empty registrations data [SQL]');
			
	}
	
	/* Check if there are new parliament members added. If yes - their names and photos are retrieved */
	$new = $db->getArray('SELECT id FROM members WHERE name = ?', array(''));
	if (!empty($new)) {
		$u->announce('Found new members! ' . json_encode($new));
		$new = $u->updateMembers($new);
		$db->insertMany('members', $new, array('id'));
		$u->announce('Added new members to the list!');
	}
	
	/* Update the details of parliament members that left the parliament early or entered the candency late */
	$list = $u->getTermDetails();
	if (is_array($list)) {
		foreach($list as $member) {
			$db->getVar('UPDATE members SET cadency_start = ?, cadency_end = ? WHERE id = ?',
				array($member['cadency_start'], $member['cadency_end'], $member['id']));
		}
		$u->announce('Updated term details');
	}
	else $u->announce('Updating term details failed!');
	
	//DONE!
	$u->announce('DONE');
	
}
else {
	echo 'Access Denied';
}
