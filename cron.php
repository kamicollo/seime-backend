<?php

if ($_SERVER['REMOTE_ADDR'] == $_SERVER['SERVER_ADDR']) {

	require_once '/home/aurimas/domains/lplius.lt/public_html/seime.lt-backend/classes/DB.php';
	
	$sql_params = array('mysql:dbname=aurimas_seime;host=localhost', 'aurimas', 'windows1257', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
	list($dsn, $username, $password, $driver_options) = $sql_params;
	$db = new DB($dsn, $username, $password, $driver_options);
	
	$old_sittings = $db->getVar('SELECT COUNT(*) FROM sittings', array());

	$command = '/usr/local/bin/php -d safe_mode=Off -d open_basedir=/ -d display_errors=true /home/aurimas/domains/lplius.lt/public_html/seime.lt-backend/update.php';
	exec($command, $output, $code);
	
	$o = implode("\n", $output);

	$new_sittings = $db->getVar('SELECT COUNT(*) FROM sittings', array());
	$prefix = '[seime.lt] [' . date('Y-m-d') . '] ';
	if ($old_sittings == $new_sittings) $subject = $prefix . 'Nepridėta posėdžių';
	else {
		$subject = $prefix . 'Pridėta posėdžių: ' . ($new_sittings - $old_sittings);
		exec('find /home/aurimas/domains/seime.lt/public_html/cache/ -name "*.cache" -type f | xargs rm');
	}
	
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
	
	echo '<strong>' . $subject . '</strong><br>';
	print_r($o);
	
	echo 'mail status:' . var_dump(mail('info@seime.lt', $subject, wordwrap($o), $headers));
	
	file_get_contents('http://seime.lt/balsavimas');
	
	$backup_command = 'mysqldump -u aurimas -pwindows1257 aurimas_seime | gzip -c > /home/aurimas/domains/seime.lt/public_html/downloads/seime.lt.gz';
	exec($backup_command);
}
else {
	echo 'Access denied';
}	

