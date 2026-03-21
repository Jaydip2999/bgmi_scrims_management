<?php
include_once __DIR__ . "/../includes/app.php";
require_admin();

$paymentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = $conn->prepare("SELECT p.*, u.is_banned FROM payments p JOIN users u ON u.id = p.user_id WHERE p.id = ?");
$stmt->bind_param("i", $paymentId);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();

if (!$payment || (int) $payment['is_banned'] === 1) {
    header("Location: payments.php");
    exit;
}

$updatePayment = $conn->prepare("UPDATE payments SET status = 'approved' WHERE id = ?");
$updatePayment->bind_param("i", $paymentId);
$updatePayment->execute();

$slot = get_next_slot($conn, (int) $payment['scrim_id']);
if ($slot === null) {
    sync_scrim_meta($conn, (int) $payment['scrim_id']);
    header("Location: payments.php");
    exit;
}

$existing = $conn->prepare("SELECT id FROM bookings WHERE user_id = ? AND scrim_id = ?");
$existing->bind_param("ii", $payment['user_id'], $payment['scrim_id']);
$existing->execute();
$booking = $existing->get_result()->fetch_assoc();

if ($booking) {
    $stmt = $conn->prepare("UPDATE bookings SET status = 'approved', slot_number = ? WHERE id = ?");
    $stmt->bind_param("ii", $slot, $booking['id']);
} else {
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, scrim_id, status, slot_number) VALUES (?, ?, 'approved', ?)");
    $stmt->bind_param("iii", $payment['user_id'], $payment['scrim_id'], $slot);
}
$stmt->execute();

sync_scrim_meta($conn, (int) $payment['scrim_id']);
create_notification($conn, (int) $payment['user_id'], (int) $payment['scrim_id'], 'system', 'Payment Approved', 'Your payment has been approved and your slot is confirmed for this scrim.', null);
header("Location: payments.php");
exit;
