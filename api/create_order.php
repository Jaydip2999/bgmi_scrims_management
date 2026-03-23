<?php
include_once __DIR__ . "/../includes/app.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Login required"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$scrim_id = (int)$data['scrim_id'];

$scrim = get_scrim_full($conn, $scrim_id);

if (!$scrim) {
    echo json_encode(["error" => "Invalid scrim"]);
    exit;
}

// Already joined check
$check = $conn->prepare("SELECT id FROM bookings WHERE user_id=? AND scrim_id=?");
$check->bind_param("ii", $_SESSION['user_id'], $scrim_id);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    echo json_encode(["error" => "Already joined"]);
    exit;
}

$amount = (int)$scrim['entry_fee'] * 100;

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.razorpay.com/v1/orders",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD => RAZORPAY_KEY_ID . ":" . RAZORPAY_KEY_SECRET,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        "amount" => $amount,
        "currency" => "INR",
        "receipt" => "scrim_" . $scrim_id . "_" . time()
    ]),
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"]
]);

$response = curl_exec($ch);
curl_close($ch);

$order = json_decode($response, true);

// Save order in DB (IMPORTANT)
$stmt = $conn->prepare("INSERT INTO payments 
(user_id, scrim_id, payment_id, status) 
VALUES (?, ?, ?, 'pending')");

$stmt->bind_param("iis", $_SESSION['user_id'], $scrim_id, $order['id']);
$stmt->execute();

echo json_encode($order);