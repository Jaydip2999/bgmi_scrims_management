<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

date_default_timezone_set('Asia/Kolkata');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bgmi_scrims";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");
$conn->query("SET time_zone = '+05:30'");
?>
