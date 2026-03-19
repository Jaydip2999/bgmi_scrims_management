<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bgmi_scrims";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");
?>
