<?php
require_once('../sc_data_recording.php');

$db = array(
	'local' => array(
		'host' => 'localhost',
		'name' => 'sc_surveys',
		'user' => 'root',
		'pass' => ''
	),
	'remote' => array(
		'host' => 'elephant.dns-systems.net',
		'port' => 3306,
		'name' => 'elephant_sc_test',
		'user' => 'elephant_sc_test',
		'pass' => 'EKt35tdb'
	)
);

$result = SC_Data_Recording::upload_latest_responses($db['local'], $db['remote']);

var_dump($result);
