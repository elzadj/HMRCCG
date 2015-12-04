<?php
date_default_timezone_set('Europe/London');
require_once('../-functions.php');
require_once('../toolkit/sc_page_generator.php');
if (session_id() == '') {
	session_start();
}


//Check for survey id
if (!$sid = get_survey_id()) {
	//Get array of survey ids
	$arr_sids = array();
	$arr_files = glob('../surveys/*.yaml');

	foreach ($arr_files as $filepath) {
		$arr_path = explode('/', $filepath);
		$filename = array_pop($arr_path);
		$arr_filename = explode('-', $filename);
		array_pop($arr_filename);
		$id = implode('-', $arr_filename);
		
		foreach ($arr_sids as $s) {
			if ($id == $s) {
				continue 2;
			}
		}
		$arr_sids[] = $id;
	}
	
	//Display links to surveys
	if (count($arr_sids) > 0) {
		echo '<h3>Please choose a survey to run</h3>';
		echo '<ul>';
		foreach ($arr_sids as $s) {
			echo '<li><a href="?sid='.$s.'">'.$s.'</a></li>';
		}
		echo '</ul>';
		exit();

	} else {
		throw new Exception('No surveys found');
	}
}



$did         = get_device_id();
$device_type = get_device_type($did);
$langCode    = isset($_SESSION['wrapper']['language']) ? $_SESSION['wrapper']['language'] : 'en';
$config      = get_config($device_type, $sid, $langCode);


//Load page generator
$page = new SC_Page_Generator($sid, FOLDER_SURVEYS);


?><!DOCTYPE HTML>
<html>
<head>
<title>Survey preview</title>
<meta charset="utf-8">
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
</head>

<body>

<?php $page->display_survey_html(!$config['settings']); ?>

</body>
</html>
