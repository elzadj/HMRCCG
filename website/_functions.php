<?php
require_once('config/db.php');
require_once('config/site.php');

function html($str) {
    return htmlspecialchars($str, ENT_COMPAT, 'UTF-8');
}

function getCMSPDO() {
    # DB Setup
    $dsn = 'mysql:host=' . DB_CMS_HOST . ';port=' . DB_CMS_PORT . ';dbname=' . DB_CMS_NAME . ';charset=utf8mb4';

    try {
        $pdo = new PDO(
            $dsn,
            DB_CMS_USER,
            DB_CMS_PASS,
            [
                PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_PERSISTENT => FALSE
            ]
        );

        return $pdo;

    } catch (PDOException $e) {
        exit($e->getMessage());
    }
}

function getSCPDO() {
    # DB Setup
    $dsn = 'mysql:host=' . DB_SC_HOST . ';port=' . DB_SC_PORT . ';dbname=' . DB_SC_NAME . ';charset=utf8mb4';

    try {
        $pdo = new PDO(
            $dsn,
            DB_SC_USER,
            DB_SC_PASS,
            [
                PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_PERSISTENT => FALSE
            ]
        );

        return $pdo;

    } catch (PDOException $e) {
        exit($ex->getMessage());
    }
}

function getLocations(&$cmsPDO) {
    $params = [];

    $sql = "SELECT `id`, `name`, `survey_id` FROM `locations`";

    if (defined('LOCATION_GROUP_ID')) {
        $sql .= "\nWHERE `group_id` = :groupID";
        $params = ['groupID' => LOCATION_GROUP_ID];
    }

    try {
        $stmt = $cmsPDO->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetchAll(PDO::FETCH_OBJ);

    } catch (PDOException $ex) {
        echo $ex->getMessage();
    }

    return $result;
}


function getLocation(&$cmsPDO, $locationID) {
    $params = [];
    $sql = "SELECT * FROM `locations` WHERE `id` = :locationID";

    $params['locationID'] = $locationID;

    try {
        $stmt = $cmsPDO->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch(PDO::FETCH_OBJ);

    } catch (PDOException $ex) {
        echo $ex->getMessage();
    }

    if (!isset($result)) {
        exit('no location with ID: ' . $locationID);
    }

    return $result;
}


function getFFTTotals(&$scPDO, $locations, $fromUNIX, $toUNIX, $surveyID, $questionID) {

    # Which surveys to query
    $surveys = isset($surveyID) && !empty($surveyID) ? [$surveyID] : array_map(function ($location) {
        return $location->survey_id;
    }, $locations);
    $surveys = array_unique($surveys);


    # Write query
    $params = [];
    $sql = "SELECT `Answer`, COUNT(`Answer`) AS `Total`
        FROM `answers`
        INNER JOIN `responses`
        ON `responses`.`ResponseID` = `answers`.`ResponseID`\n";

    $sql .= "WHERE `SurveyID` IN (" . implode(', ', array_fill(0, count($surveys), '?')) . ")\n";

    foreach ($surveys as $survey) {
        $params[] = $survey;
    }

    $sql .= "AND `Question` = ?
        AND `Status` IN ('uploaded', 'downloaded')
        AND `Created` >= ?
        AND `Created` < ?
        GROUP BY `Answer`";

    $params[] = $questionID;
    $params[] = $fromUNIX;
    $params[] = $toUNIX;

    //var_dump($sql, $params);

    # Run query
    try {
        $stmt = $scPDO->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetchAll(PDO::FETCH_OBJ);

        return $result;

    } catch (PDOException $ex) {
        echo $ex->getMessage();
    }
}


function getFFTData(&$scPDO, $locations, $fromUNIX = NULL, $toUNIX = NULL, $surveyID = NULL) {
    $fftData = [
        'extremely likely'            => 0,
        'likely'                      => 0,
        'neither likely nor unlikely' => 0,
        'unlikely'                    => 0,
        'extremely unlikely'          => 0,
        'don\'t know'                 => 0
    ];


    # Input
    $from     = isset($fromUNIX) ? strtotime($fromUNIX) : 0;
    $to       = isset($toUNIX)   ? strtotime($toUNIX)   : time();

    # Get totals from database
    $result = getFFTTotals($scPDO, $locations, $fromUNIX, $toUNIX, $surveyID, QUESTION_ID);
    
    foreach ($result as $row) {
        $fftData[strtolower($row->Answer)] = (int)$row->Total;
    }

    return $fftData;
}


function getComments(&$cmsPDO, $surveyID) {
    $params = [];
    $sql = "SELECT `id`, `comment`, `submitted_at`, `reply`
        FROM `comments`
        WHERE `survey_id` = :surveyID
        AND `display` = :display
        ORDER BY `submitted_at` DESC";

    $params['surveyID'] = $surveyID;
    $params['display'] = 'yes';
    
    
    # Process results
    try {
        $stmt = $cmsPDO->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetchAll(PDO::FETCH_OBJ);

    } catch (PDOException $ex) {
        echo $ex->getMessage();
    }

    return $result;
}


function formatComment($str) {
    ## New lines are converted to paragraphs
    $str = html($str);
    $str = str_replace("\r\n", "\n", $str);
    $paragraphs = explode("\n", $str);
    $paragraphs = array_filter($paragraphs, 'strlen');

    return '<p>' . implode('</p><p>', $paragraphs) . '</p>';
}
