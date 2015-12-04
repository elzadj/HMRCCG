<?php
date_default_timezone_set('Europe/London');
require_once('../config/dbs.php');
require_once('../-functions.php');
require_once('../toolkit/sc_data_recording.php');
if (session_id() == '') {
	session_start();
}

set_time_limit(180);

$status = SC_Data_Recording::upload_latest_responses($dbs['local'], $dbs['remote']);

exit('Uploaded '.$status['uploaded'].' of '.$status['total'].' responses');
