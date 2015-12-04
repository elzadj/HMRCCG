<?php
require_once('../sc_data_recording.php');

$survey_id = 'test_questions';

$db = array(
	'host' => 'elephant.dns-systems.net',
	'port' => 3306,
	'name' => 'elephant_sc_test',
	'user' => 'elephant_sc_test',
	'pass' => 'EKt35tdb'
);

$local = FALSE;

$data = SC_Data_Recording::from_database($survey_id, $db, $local);

var_dump($data);
