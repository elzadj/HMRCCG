<?php
date_default_timezone_set('Europe/London');
require_once('../config/dbs.php');
require_once('../-functions.php');
require_once('../toolkit/sc_data_recording.php');

if (isset($_GET['local'])) {
	if ($_GET['local'] === 'true') {
		# Create local database
		SC_Data_Recording::create_database($dbs['local'], TRUE);
		exit('Local database created');

	}

	if ($_GET['local'] === 'false') {
		# Create remote database
		SC_Data_Recording::create_database($dbs['remote'], FALSE);
		exit('Remote database created');

	}

	throw new Exception('set "local" to "true" or "false"');
	
}

throw new Exception('run again with querystring var "local" set to "true" or "false"');
