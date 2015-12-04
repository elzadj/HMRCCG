<?php
date_default_timezone_set('Europe/London');
require_once('../-functions.php');
require_once('../toolkit/sc_survey_flow.php');
if (session_id() == '') {
	session_start();
}


$surveyID    = get_survey_id();
$deviceID    = get_device_id();
$device_type = get_device_type($deviceID);
$langCode    = isset($_SESSION['wrapper']['language']) ? $_SESSION['wrapper']['language'] : 'en';
$config      = get_config($device_type, $surveyID, $langCode);


# Load rules
if ($survey = new SC_Survey_Flow($surveyID, FOLDER_SURVEYS, $langCode)) {
	//Config
	$survey->bool_store_bwd = TRUE;
	
	//Process answers to questions
	if (isset($_POST['sc_page_id'])) {

		// Languages
		if (isset($_POST['q_language'])) {
			$langCode = $_POST['q_language'];

			if (!is_null($langCode) && !empty($langCode)) {
				$_SESSION['wrapper']['language'] = $langCode;
			}
		}

		$next_id = $survey->go();

	} else {
		$first_id = $survey->obj_page_generator->get_first_page_id();
		SC_Survey_Flow::reset_survey($first_id);
		throw new Exception('no page id posted');
	}
	
	//Load next page or submit response
	if ($next_id === SC_Page_Generator::SUBMIT_RESPONSE) {
		//Submit survey response
		header('Location:process_response.php?sid=' . rawurlencode($surveyID) . '&did=' . rawurlencode($deviceID));
		exit();

	} elseif ($next_id === SC_Page_Generator::START) {
		//Back to intro
		header('Location:../?pagetype=intro');
		exit();
		
	} else {
		//Load next page
		header('Location:../?pagetype=survey&pid='.rawurlencode($next_id));
		//header('Refresh:4;../?pagetype=survey&pid='.rawurlencode($next_id));
		exit();
	}

} else {
	//unable to load survey, PHP errors should show
	session_unset();
	throw new Exception('could not load survey: ' . $surveyID);
}
