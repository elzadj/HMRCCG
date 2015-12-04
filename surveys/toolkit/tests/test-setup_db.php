<?php
require_once('../sc_data_recording.php');

$db = array(
	'host' => 'localhost',
	'name' => 'sc_surveys',
	'user' => 'root',
	'pass' => ''
);

SC_Data_Recording::create_database($db, TRUE);
echo 'Database created';
