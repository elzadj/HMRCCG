<?php
require_once('../sc_data_recording.php');
date_default_timezone_set('Europe/London');

// $sc_dr = new SC_Data_Recording('test_survey');

$survey_id = 'sc_test_survey';

$arr_response = array(
	'q_one'   => 'Tom',
	'q_two'   => 'likes',
	'q_three' => array(
		'chocolate',
		'roast lamb',
		'cheese',
		'pork pies'
	)
);

$db = array(
	'host' => 'localhost',
	'name' => 'sc_test',
	'user' => 'root',
	'pass' => ''
);

$local = TRUE;
$created_time = strtotime('-10 minutes');
$device_id = 'SC Test Page';

$rid = SC_Data_Recording::to_database($survey_id, $arr_response, $db, $local, $created_time, $device_id);
echo 'Response recorded: ' . $rid;
