<?php
include_once __DIR__ . "/../includes/app.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Login required"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$userId = (int) $_SESSION['user_id'];
$scrimId = isset($data['scrim_id']) ? (int) $data['scrim_id'] : 0;
$paymentId = trim((string) ($data['payment_id'] ?? ''));
$orderId = trim((string) ($data['order_id'] ?? ''));
$signature = trim((string) ($data['signature'] ?? ''));

if ($scrimId <= 0 || $paymentId === '' || $orderId === '' || $signature === '') {
    http_response_code(400);
    echo json_encode(["error" => "Incomplete payment data"]);
    exit;
}

$scrim = get_scrim_full($conn, $scrimId);
if (!$scrim) {
    http_response_code(404);
    echo json_encode(["error" => "Invalid scrim"]);
    exit;
}

$paymentStmt = $conn->prepare("SELECT id FROM payments WHERE user_id = ? AND scrim_id = ? AND gateway_order_id = ? ORDER BY id DESC LIMIT 1");
$paymentStmt->bind_param("iis", $userId, $scrimId, $orderId);
$paymentStmt->execute();
$payment = $paymentStmt->get_result()->fetch_assoc();

if (!$payment) {
    http_response_code(404);
    echo json_encode(["error" => "Payment order not found"]);
    exit;
}

$bookingCheck = $conn->prepare("SELECT id FROM bookings WHERE user_id = ? AND scrim_id = ?");
$bookingCheck->bind_param("ii", $userId, $scrimId);
$bookingCheck->execute();
if ($bookingCheck->get_result()->num_rows > 0) {
    echo json_encode(["message" => "Already joined"]);
    exit;
}

$generatedSignature = hash_hmac(
    'sha256',
    $orderId . "|" . $paymentId,
    razorpay_key_secret()
);

if (!hash_equals($generatedSignature, $signature)) {
    http_response_code(400);
    echo json_encode(["error" => "Payment verification failed"]);
    exit;
}

$slot = get_next_slot($conn, $scrimId);
if ($slot === null) {
    http_response_code(409);
    echo json_encode(["error" => "Slots full"]);
    exit;
}

$update = $conn->prepare("UPDATE payments
    SET gateway_payment_id = ?, gateway_signature = ?, transaction_ref = ?, status = 'approved'
    WHERE id = ?");
$update->bind_param("sssi", $paymentId, $signature, $paymentId, $payment['id']);
$update->execute();

$insertBooking = $conn->prepare("INSERT INTO bookings (user_id, scrim_id, slot_number, status)
    VALUES (?, ?, ?, 'approved')");
$insertBooking->bind_param("iii", $userId, $scrimId, $slot);
$insertBooking->execute();

sync_scrim_meta($conn, $scrimId);
create_notification($conn, $userId, $scrimId, 'system', 'Payment Approved', 'Your Razorpay payment was confirmed and your slot is booked.', null);

echo json_encode([
    "message" => "Payment successful",
    "slot" => $slot
]);
