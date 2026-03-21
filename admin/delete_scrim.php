<?php
include_once __DIR__ . "/../includes/app.php";
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit;
}

$scrimId = isset($_POST['scrim_id']) ? (int) $_POST['scrim_id'] : 0;
if ($scrimId <= 0) {
    header("Location: dashboard.php?error=invalid_scrim");
    exit;
}

$check = $conn->prepare("SELECT id FROM scrims WHERE id = ? LIMIT 1");
$check->bind_param("i", $scrimId);
$check->execute();

if (!$check->get_result()->fetch_assoc()) {
    header("Location: dashboard.php?error=missing_scrim");
    exit;
}

$deleteNotifications = $conn->prepare("DELETE FROM notifications WHERE scrim_id = ?");
$deleteNotifications->bind_param("i", $scrimId);
$deleteNotifications->execute();

$deleteScrim = $conn->prepare("DELETE FROM scrims WHERE id = ?");
$deleteScrim->bind_param("i", $scrimId);
$deleteScrim->execute();

header("Location: dashboard.php?deleted=1");
exit;
?>
