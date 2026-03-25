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
$scrim_id = isset($data['scrim_id']) ? (int) $data['scrim_id'] : 0;

$scrim = get_scrim_full($conn, $scrim_id);

if (!$scrim) {
    http_response_code(404);
    echo json_encode(["error" => "Invalid scrim"]);
    exit;
}

if (!registration_is_open($scrim)) {
    http_response_code(400);
    echo json_encode(["error" => "Registration closed"]);
    exit;
}

if (!razorpay_is_configured()) {
    http_response_code(503);
    echo json_encode(["error" => "Razorpay is not configured"]);
    exit;
}

// Already joined check
$check = $conn->prepare("SELECT id FROM bookings WHERE user_id=? AND scrim_id=?");
$check->bind_param("ii", $_SESSION['user_id'], $scrim_id);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    http_response_code(409);
    echo json_encode(["error" => "Already joined"]);
    exit;
}

$identityConflict = find_scrim_identity_conflict($conn, $scrim_id, (int) $_SESSION['user_id']);
if ($identityConflict) {
    http_response_code(409);
    echo json_encode(["error" => scrim_identity_conflict_message($identityConflict['field'])]);
    exit;
}

$amount = (int)$scrim['entry_fee'] * 100;

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.razorpay.com/v1/orders",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD => razorpay_key_id() . ":" . razorpay_key_secret(),
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        "amount" => $amount,
        "currency" => "INR",
        "receipt" => "scrim_" . $scrim_id . "_" . time()
    ], JSON_UNESCAPED_SLASHES),
    CURLOPT_TIMEOUT => 20,
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"]
]);

$response = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

$order = json_decode($response, true);

if ($response === false || $curlError !== '') {
    http_response_code(502);
    echo json_encode(["error" => "Unable to reach Razorpay"]);
    exit;
}

if (!is_array($order) || $httpCode >= 400 || empty($order['id'])) {
    http_response_code(502);
    echo json_encode(["error" => $order['error']['description'] ?? "Unable to create Razorpay order"]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO payments 
(user_id, scrim_id, amount, gateway_order_id, status) 
VALUES (?, ?, ?, ?, 'pending')");

$dbAmount = (float) $scrim['entry_fee'];
$stmt->bind_param("iids", $_SESSION['user_id'], $scrim_id, $dbAmount, $order['id']);
$stmt->execute();

echo json_encode([
    "id" => $order['id'],
    "amount" => $order['amount'],
    "currency" => $order['currency'],
    "key" => razorpay_key_id(),
    "name" => site_name(),
    "description" => $scrim['title'] . " Entry Fee"
]);
