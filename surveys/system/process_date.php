<?php
date_default_timezone_set('Europe/London');
require_once('../-functions.php');
session_start();

$unix_time = mktime(
	(int)$_POST['manual_time_hour'],
	(int)$_POST['manual_time_min'],
	0,
	(int)$_POST['manual_time_month'],
	(int)$_POST['manual_time_day'],
	(int)$_POST['manual_time_year']
);

$_SESSION['wrapper']['date'] = $unix_time;
$_SESSION['deviceID'] = 'data-entry';

header('Location:..?pagetype=survey');
