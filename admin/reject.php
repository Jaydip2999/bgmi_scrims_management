<?php
include_once __DIR__ . "/../includes/app.php";
require_admin();

$paymentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = $conn->prepare("SELECT * FROM payments WHERE id = ?");
$stmt->bind_param("i", $paymentId);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();

if ($payment) {
    $update = $conn->prepare("UPDATE payments SET status = 'rejected' WHERE id = ?");
    $update->bind_param("i", $paymentId);
    $update->execute();

    $booking = $conn->prepare("UPDATE bookings SET status = 'rejected', slot_number = NULL WHERE user_id = ? AND scrim_id = ?");
    $booking->bind_param("ii", $payment['user_id'], $payment['scrim_id']);
    $booking->execute();
    sync_scrim_meta($conn, (int) $payment['scrim_id']);
}

header("Location: payments.php");
exit;
