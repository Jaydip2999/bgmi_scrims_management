<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

date_default_timezone_set('Asia/Kolkata');

$hostName = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
$isLocalHost = in_array($hostName, ['', 'localhost', '127.0.0.1'], true);

$localConfig = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'name' => 'bgmi_scrims',
];

$productionConfig = [
    'host' => 'sql213.infinityfree.com',
    'user' => 'if0_41472130',
    'pass' => 'UMVpfJJaHY',
    'name' => 'if0_41472130_bgmi_scrims',
];

$dbConfig = $isLocalHost ? $localConfig : $productionConfig;

$servername = getenv('DB_HOST') ?: $dbConfig['host'];
$username = getenv('DB_USER') ?: $dbConfig['user'];
$password = getenv('DB_PASS') ?: $dbConfig['pass'];
$dbname = getenv('DB_NAME') ?: $dbConfig['name'];

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");
$conn->query("SET time_zone = '+05:30'");


?>
