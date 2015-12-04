<?php
date_default_timezone_set('Europe/London');
require_once('../-functions.php');
require_once('../toolkit/sc_survey_flow.php');
if (session_id() == '') {
    session_start();
}

# Development mode
if (isset($_GET['dev'])) {
    switch (strtolower($_GET['dev'])) {
        case 'off':
            $devMode = FALSE;
            break;

        default:
        case 'on':
            $devMode = TRUE;
            break;
    }

    $_SESSION['devMode'] = $devMode;

} else {
    $devMode = isset($_SESSION['devMode']) ? $_SESSION['devMode'] : FALSE;
}


# Check for reset
if (isset($_GET['reset'])) {
    unset($_SESSION['wrapper']);
    unset($_SESSION['sc']);
}



# Check for survey id
if (!$surveyID = get_survey_id()) {
    ## Get array of survey ids
    $arr_sids = array();
    $arr_folders = glob(FOLDER_SURVEYS . '*', GLOB_ONLYDIR);
    
    foreach ($arr_folders as $path) {
        $arr_path = explode('/', $path);
        $id = array_pop($arr_path);

        if (substr($id, 0, 1) != '_' && $id !== 'assets') {
            $arr_sids[] = $id;
        }
    }
    
    ## Display links to surveys
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


# Check for device id
$deviceID    = get_device_id();
$device_type = get_device_type($deviceID);


# Check for language
$langCode = isset($_SESSION['wrapper']['language']) && !empty($_SESSION['wrapper']['language']) ? $_SESSION['wrapper']['language'] : 'en';


# Load config files
$config = get_config($device_type, $surveyID, $langCode);


# Exit button label
$url_menu = config_var_exists($config, 'urls', 'menu') ? str_replace('{DID}', $deviceID, $config['urls']['menu']) : NULL;


# Check for initial answers
$initialAnswers = [
    'h_device' => $deviceID
];

if (isset($_GET['init']) && strlen($_GET['init']) > 0) { // Prioritise querystring
    $initialAnswers = array_merge($initialAnswers, json_decode($_GET['init'], TRUE));
    $_SESSION['wrapper']['init'] = $initialAnswers;

} elseif (isset($_SESSION['wrapper']['init']) && count($_SESSION['wrapper']['init']) > 0) { // Use session, if available
    $initialAnswers = $_SESSION['wrapper']['init'];

}


# Get intro and outro paths
$introPath = getFilePathWithFallbacks('intro.md', $surveyID, $langCode, !$devMode);
$outroPath = getFilePathWithFallbacks('outro.md', $surveyID, $langCode, !$devMode);


# Show page
$page = new SC_Page_Generator($surveyID, FOLDER_SURVEYS, $langCode);

$pageType = isset($_GET['pagetype']) ? $_GET['pagetype'] : 'intro';


## Load intro
if ($pageType === 'intro') {
    ### Load intro into string
    if (is_null($introPath)) {
        #### No intro - start survey
        $pageType = 'survey';

    } elseif (isset($_SESSION['wrapper']['data'][$surveyID][$langCode]['intro.md'])) {
        #### Load intro from session
        $content = $_SESSION['wrapper']['data'][$surveyID][$langCode]['intro.md'];

    } else {
        #### Load intro from file
        $content = file_get_contents($introPath);

        if ($devMode === FALSE) {
            $_SESSION['wrapper']['data'][$surveyID][$langCode]['intro.md'] = $content;
        }
    }
}

## Get required content
switch ($pageType) {
    case 'date':
        break;


    case 'survey':
        $page->bool_ignore_defaults =
            config_var_exists($config, 'settings', 'ignoreDefaults') ?
            $config['settings']['ignoreDefaults'] :
            TRUE;
        
        ### Check for page id and reset survey if none exists
        $firstPageID = $page->get_first_page_id();

        if (isset($_GET['pid']) && strlen($_GET['pid']) > 0) {
            $pageID = $_GET['pid'];
            
        } else { 
            ### Start survey
            $pageID = $firstPageID;
            SC_Survey_Flow::reset_survey($pageID, $initialAnswers);
        }
        break;


    case 'thankyou':
        ### Forget device ID if web or data-entry, to allow staff to switch between the two
        if ($_SESSION['deviceID'] === 'web' || $_SESSION['deviceID'] === 'data-entry') {
            unset($_SESSION['deviceID']);
        }
        
        ### Load outro into string
        if (is_null($outroPath)) {
            #### No outro - start survey
            header('Location:' . $url_menu);

        } elseif (isset($_SESSION['wrapper']['data'][$surveyID][$langCode]['outro.md'])) {
            #### Load outro from session
            $content = $_SESSION['wrapper']['data'][$surveyID][$langCode]['outro.md'];

        } else {
            #### Load outro from file
            $content = file_get_contents($outroPath);

            if ($devMode == FALSE) {
                $_SESSION['wrapper']['data'][$surveyID][$langCode]['outro.md'] = $content;
            }
        }
        break;
}


# Get theme
$theme    = isset($config['theme']) ? $config['theme'] : 'Basic';

$imgURL   = 'ui/img/';
$jsURL    = 'ui/js/';
$themeURL = 'ui/themes/' . $theme . '/';


# HTML
ob_start('ob_gzhandler');
include_once('../' . $themeURL . 'index.php');
ob_end_flush();
