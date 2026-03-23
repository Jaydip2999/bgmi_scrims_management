<?php
include_once __DIR__ . "/../includes/app.php";

$data = json_decode(file_get_contents("php://input"), true);

$user_id = $_SESSION['user_id'];
$scrim_id = (int)$data['scrim_id'];
$payment_id = $data['payment_id'];
$order_id = $data['order_id'];
$signature = $data['signature'];

// 🔐 VERIFY SIGNATURE
$generated_signature = hash_hmac(
    'sha256',
    $order_id . "|" . $payment_id,
    RAZORPAY_KEY_SECRET
);

if ($generated_signature !== $signature) {
    echo "Payment verification failed!";
    exit;
}

// Update payment status
$update = $conn->prepare("UPDATE payments 
SET payment_id=?, status='success' 
WHERE payment_id=?");

$update->bind_param("ss", $payment_id, $order_id);
$update->execute();

// Duplicate booking check
$check = $conn->prepare("SELECT id FROM bookings WHERE user_id=? AND scrim_id=?");
$check->bind_param("ii", $user_id, $scrim_id);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    echo "Already joined!";
    exit;
}

// Slot calculation
$q = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE scrim_id=? AND status='approved'");
$q->bind_param("i", $scrim_id);
$q->execute();

$total = $q->get_result()->fetch_assoc()['total'];
$slot = $total + 1;

// Scrim check
$scrim = get_scrim_full($conn, $scrim_id);

if ($slot > $scrim['total_slots']) {
    echo "Slots full!";
    exit;
}

// Insert booking
$stmt = $conn->prepare("INSERT INTO bookings 
(user_id, scrim_id, slot_number, status) 
VALUES (?, ?, ?, 'approved')");

$stmt->bind_param("iii", $user_id, $scrim_id, $slot);
$stmt->execute();

echo "✅ Payment successful! Slot No: " . $slot;