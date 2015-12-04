<?php
date_default_timezone_set('Europe/London');
require_once('../config/dbs.php');
require_once('../-functions.php');
require_once('../toolkit/sc_data_recording.php');
if (session_id() == '') {
	session_start();
}


$sid         = get_survey_id();
$did         = get_device_id();
$device_type = get_device_type($did);
$langCode    = isset($_SESSION['wrapper']['language']) ? $_SESSION['wrapper']['language'] : 'en';
$config      = get_config($device_type, $sid, $langCode);

$response    = $_SESSION['sc']['response'];

//Avoid duplication
$duplicate_response = FALSE;
if (isset($_SESSION['urids'])) {
	//Loop through stored urids
	foreach ($_SESSION['urids'] as $urid) {
		if ($urid == $response['h_unique_response_id']) {
			//Response has already been noted, so this must be a duplicate submission
			$duplicate_response = TRUE;
			break;
		}
	}
	//Store this urid
	$_SESSION['urids'][] = $response['h_unique_response_id'];
	
} else {
	//Create urid library and store this urid for future testing
	$_SESSION['urids'] = array($response['h_unique_response_id']);
}

//Save the response
if (!$duplicate_response ) {
	//Complete response with system data
	$response['h_finish_time'] = isset($_SESSION['wrapper']['date']) ? $_SESSION['wrapper']['date'] : time();
	$response['h_duration'] = isset($response['h_start_time']) && $response['h_start_time'] > 0
		? (string)((int)$response['h_finish_time'] - (int)$response['h_start_time']) : '';
	
	//Set database to local or remote
	if ($config['isOffline']) {
		$db = $dbs['local'];
		$local = TRUE;

	} else {
		$db = $dbs['remote'];
		$local = FALSE;
	}


	//Save the response to the database
	$response_id = SC_Data_Recording::to_database(
		$sid,
		$response,
		$db,
		$local,
		$response['h_finish_time'],
		$did
	);
}

unset($_SESSION['wrapper']['date']);


//Load thank you page
header('Location:../?pagetype=thankyou');
exit();
