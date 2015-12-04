<?php
require_once('vendor/autoload.php');

# Constants
define('FOLDER_SC', dirname(__FILE__) . '/');
define('FOLDER_SURVEYS', FOLDER_SC . 'surveys/');

define('DTYPE_KIOSK', 'kiosk');
define('DTYPE_HANDHELD', 'handheld');
define('DTYPE_WEB', 'web');


# Functions
function html($str) {
    return htmlspecialchars($str, ENT_COMPAT, 'UTF-8');
}

function get_survey_id() {
    if (isset($_GET['sid']) && strlen($_GET['sid']) > 0) {
        $sid = $_GET['sid'];
        $_SESSION['wrapper']['surveyID'] = $sid;
        return $sid;

    }

    if (isset($_SESSION['wrapper']['surveyID']) && strlen($_SESSION['wrapper']['surveyID']) > 0) {
        return $_SESSION['wrapper']['surveyID'];
    }

    return FALSE;
}

function get_device_id() {
    if (isset($_GET['did']) && strlen($_GET['did']) > 0) {
        $did = $_GET['did'];
        $_SESSION['deviceID'] = $did;
        return $did;

    }

    if (isset($_GET['urn']) && strlen($_GET['urn']) > 0) { // to allow old web links to work
        $did = $_GET['urn'];
        $_SESSION['deviceID'] = $did;
        return $did;

    }

    if (isset($_GET['id']) && strlen($_GET['id']) > 0) { // to allow old web links to work
        $did = $_GET['id'];
        $_SESSION['deviceID'] = $did;
        return $did;

    }

    # Fallback to session
    if (isset($_SESSION['deviceID']) && strlen($_SESSION['deviceID']) > 0) {
        return $_SESSION['deviceID'];
    }

    # Fallback to 'web'
    $did = 'web';
    $_SESSION['deviceID'] = $did;
    return $did;
}

function get_device_type($deviceID) {
    switch (substr($deviceID, 0, 2)) {
        case 'K_':
            return DTYPE_KIOSK;
            break;

        case 'M_':
            return DTYPE_HANDHELD;
            break;

        default:
            return DTYPE_WEB;
    }
}

# Recursively merge arrays in reverse priority order
# Does not keep a history, like array_merge_recursive()
function proper_merge() {
    function dissect_array($arr, &$r) {
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                dissect_array($v, $r[$k]);
            } else {
                $r[$k] = $v;
            }
        }
    }

    $args = func_get_args();
    $result = array();

    foreach ($args as $a) {
        if (isset($a) && is_array($a)) {
            dissect_array($a, $result);
        }
    }

    return $result;
}


function markdown($md, $inlineOnly = FALSE) {
    $parsedown = new Parsedown();
    return $inlineOnly !== FALSE ? $parsedown->line($md) : $parsedown->text($md);
}


function getFilePathWithFallbacks($filename, $surveyID, $langCode) {

    # Default to language-specific folder
    $filepath = FOLDER_SURVEYS . $surveyID . '/' . $langCode . '/' . $filename;
    if (file_exists($filepath)) {
        return $filepath;
    }
    
    # Fallback to survey folder
    $filepath = FOLDER_SURVEYS . $surveyID . '/' . $filename;
    if (file_exists($filepath)) {
        return $filepath;
    }
    
    # Fallback to root surveys folder
    $filepath = FOLDER_SURVEYS . $filename;
    if (file_exists($filepath)) {
        return $filepath;
    }
    
    # Fallback to NULL
    return NULL;
}


function get_config($device_type = NULL, $survey_id = NULL, $langCode) {
    $main_config     = array();
    $device_config   = array();
    $survey_config   = array();
    $language_config = array();

    if (isset($device_type)) {
        switch ($device_type) {
            case DTYPE_KIOSK:
                $device_file = 'config_kiosk.json';
                break;

            case DTYPE_HANDHELD:
                $device_file = 'config_handheld.json';
                break;

            case DTYPE_WEB:
                $device_file = 'config_web.json';
                break;
        }
    }

    ## Load primary config file
    try {
        $json = file_get_contents(FOLDER_SC . 'config/config.json');

        $main_config = json_decode($json, TRUE);
        if (!isset($main_config)) {
            throw new Exception('Config file not valid JSON.');
        }

    } catch (Exception $e) {
        throw new Exception('Could not load config file.');
    }

    # Load device configuration file
    if (isset($device_file) && file_exists(FOLDER_SC . 'config/' . $device_file)) {
        try {
            $json = file_get_contents(FOLDER_SC . 'config/' . $device_file);

            $device_config = json_decode($json, TRUE);
            if (!isset($device_config)) {
                throw new Exception('Device config file not valid JSON.');
            }

        } catch (Exception $e) {
        }
    }

    # Load survey configuration file
    if (isset($survey_id) && file_exists(FOLDER_SURVEYS . $survey_id . '/config.json')) {
        try {
            $json = file_get_contents(FOLDER_SURVEYS . $survey_id . '/config.json');

            $survey_config = json_decode($json, TRUE);
            if (!isset($survey_config)) {
                throw new Exception('Survey config file not valid JSON.');
            }

        } catch (Exception $e) {
        }
    }

    # Load survey language configuration file
    if (isset($survey_id) && file_exists(FOLDER_SURVEYS . $survey_id . '/' . $langCode . '/config.json')) {
        try {
            $json = file_get_contents(FOLDER_SURVEYS . $survey_id . '/' . $langCode . '/config.json');

            $language_config = json_decode($json, TRUE);
            if (!isset($survey_config)) {
                throw new Exception('Survey language config file not valid JSON.');
            }

        } catch (Exception $e) {
        }
    }

    return proper_merge($main_config, $device_config, $survey_config, $language_config);
}


function config_var_exists($config) {
    $arg_list = func_get_args();
    array_shift($arg_list);
    $new_list = $config;

    for ($i = 0; $i < count($arg_list); $i++) {
        if (!array_key_exists($arg_list[$i], $new_list)) {
            return FALSE;
        }

        $new_list = $new_list[$arg_list[$i]];
    }

    return TRUE;
}
