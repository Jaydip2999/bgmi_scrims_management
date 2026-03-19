<?php
include_once __DIR__ . "/../includes/app.php";
require_login();

$userId = (int) $_SESSION['user_id'];
$scrimId = isset($_POST['scrim_id']) ? (int) $_POST['scrim_id'] : 0;
$transactionRef = trim($_POST['transaction_ref'] ?? '');
$scrim = get_scrim_full($conn, $scrimId);

if (
    !$scrim ||
    !registration_is_open($scrim) ||
    !isset($_FILES['screenshot']) ||
    $_FILES['screenshot']['error'] !== UPLOAD_ERR_OK
) {
    header("Location: ../pages/payment.php?scrim_id=" . $scrimId);
    exit;
}

$uploadDir = __DIR__ . "/../assets/payments";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$extension = pathinfo($_FILES['screenshot']['name'], PATHINFO_EXTENSION);
$safeName = "payment_" . $userId . "_" . $scrimId . "_" . time() . ($extension ? "." . preg_replace('/[^a-zA-Z0-9]/', '', $extension) : '');
$targetPath = $uploadDir . "/" . $safeName;
move_uploaded_file($_FILES['screenshot']['tmp_name'], $targetPath);

$relativePath = "assets/payments/" . $safeName;
$stmt = $conn->prepare("INSERT INTO payments (user_id, scrim_id, amount, screenshot, transaction_ref, status)
    VALUES (?, ?, ?, ?, ?, 'pending')");
$amount = (float) $scrim['entry_fee'];
$stmt->bind_param("iidss", $userId, $scrimId, $amount, $relativePath, $transactionRef);
$stmt->execute();

$booking = $conn->prepare("INSERT INTO bookings (user_id, scrim_id, status)
    VALUES (?, ?, 'pending')
    ON DUPLICATE KEY UPDATE status = 'pending'");
$booking->bind_param("ii", $userId, $scrimId);
$booking->execute();

header("Location: ../pages/scrim-details.php?id=" . $scrimId);
exit;
