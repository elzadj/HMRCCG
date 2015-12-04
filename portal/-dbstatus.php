<?php
# Get number of surveys waiting to upload
error_reporting(0);
header('Content-Type: application/json');

$dsn = 'mysql:host=127.0.0.1;dbname=sc_surveys';

try {
    $pdo = new PDO($dsn, 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    exit('{ "error": "could not connect to local database" }');
}

# Construct SQL
$sql = "SELECT (
    SELECT COUNT(*)
    FROM `responses`
    WHERE `Status` = 'incomplete'
) AS `incomplete`, (
    SELECT COUNT(*)
    FROM `responses`
    WHERE `Status` = 'ready to upload'
) AS `ready`, (
    SELECT COUNT(*)
    FROM `responses`
    WHERE `Status` = 'uploading'
) AS `uploading`, (
    SELECT COUNT(*)
    FROM `responses`
    WHERE `Status` = 'uploaded'
) AS `uploaded`";


# Query database
try {
	$stmt = $pdo->query($sql);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	exit(json_encode($row, JSON_NUMERIC_CHECK));

} catch (PDOException $e) {
	exit('{ "error": "could not query local database" }');
}
