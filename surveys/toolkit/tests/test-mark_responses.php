<?php
require_once('../sc_data_recording.php');

$arr_ids = array(
	'1379937770-test_questions-unknown device',
	'response_c',
	'1379937848-test_languages-test'
);

$db = array(
	'host' => 'elephant.dns-systems.net',
	'port' => 3306,
	'name' => 'elephant_sc_test',
	'user' => 'elephant_sc_test',
	'pass' => 'EKt35tdb'
);

$local = TRUE;

SC_Data_Recording::mark_as_downloaded($arr_ids, $db, $local);
echo 'Responses marked as downloaded.';
