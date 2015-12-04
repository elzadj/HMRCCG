<?php
session_start();

define('DEVICE_KIOSK', 'kiosk');
define('DEVICE_HANDHELD', 'handheld');
define('DEVICE_WEB', 'web');


# Get Device ID
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $deviceID = $_SESSION['id'] = $_GET['id'];
} elseif (isset($_SESSION['id'])) {
    $deviceID = $_SESSION['id'];
} else {
    exit('no `id` var in querystring');
}


# Load config
if (file_exists('config/config.json')) {
    $json = file_get_contents('config/config.json');
    if (!$config = json_decode($json, FALSE)) {
        exit('Invalid config file');
    }
}


# Load customer data
if (!file_exists('config/practices.json')) {
    exit('No practices file');
}

$json = file_get_contents('config/practices.json');
if (!$practices = json_decode($json, FALSE)) {
    exit('Invalid data file');
}

$practiceID = isset($_GET['as']) ? $_GET['as'] : $deviceID;

$arr = array_filter($practices, function ($el) use ($practiceID) {
    return $el->id === $practiceID;
});

if (!count($arr)) {
    exit('Device not found in data file');
}
$practice = array_values($arr)[0];
$locationTitle = $practice->title;


# Shared locations
if (file_exists('config/shared-locations.json')) {
    $json = file_get_contents('config/shared-locations.json');
    $sharedLocations = json_decode($json, FALSE);

    if (!isset($sharedLocations)) {
        exit('invalid JSON shared-locations.json');
    }

    foreach ($sharedLocations as $loc) {
        if (in_array($deviceID, $loc->devices)) {
            $locationTitle = $loc->location;

            $sharedPractices = array_filter($practices, function ($el) use ($loc) {
                return in_array($el->id, $loc->devices);
            });
            
            break;
        }
    }
}


# Device type
switch (substr($deviceID, 0, 2)) {
    case 'K_':
        $deviceType = DEVICE_KIOSK;
        break;

    case 'M_':
        $deviceType = DEVICE_HANDHELD;
        break;

    default:
        $deviceType = DEVICE_WEB;
}


# Connectivity
$offlineDevice = FALSE;
if (isset($config)) {
    $offlineDevice = $config->devices->{$deviceType}->isOffline;
}

require_once('vendor/parsedown/parsedown.php');
require_once('vendor/parsedown/parsedown-extra.php');
$parsedown = new ParsedownExtra();

?><!doctype html>
<html lang="en" class="no-js">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title><?php echo html($locationTitle); ?></title>
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/styles.css">
    <?php if ($deviceType === DEVICE_HANDHELD) { ?>
    <link rel="stylesheet" href="vendor/vex/vex.css">
    <link rel="stylesheet" href="vendor/vex/vex-theme-os.css">
    <?php } ?>
    <?php if (file_exists('config/styles.css')) { ?>
    <link rel="stylesheet" href="config/styles.css">
    <?php } ?>
    <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Ubuntu:400,600|Open+Sans:400,600">
</head>


<body class="<?php echo $page; ?> container">
    
    <?php if (file_exists('config/header.php')) { ?>
    <header class="app-header">
        <?php include('config/header.php'); ?>
    </header>
    <?php } ?>
