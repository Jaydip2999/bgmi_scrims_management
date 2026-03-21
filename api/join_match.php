<?php
include_once __DIR__ . "/../includes/app.php";
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'login_required']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$scrimId = isset($_POST['scrim_id']) ? (int) $_POST['scrim_id'] : 0;
$scrim = get_scrim_full($conn, $scrimId);
if (!$scrim) {
    echo json_encode(['status' => 'error', 'message' => 'invalid_scrim']);
    exit;
}

if (!registration_is_open($scrim)) {
    echo json_encode(['status' => 'error', 'message' => 'registration_closed']);
    exit;
}

$identityConflict = find_scrim_identity_conflict($conn, $scrimId, $userId);
if ($identityConflict) {
    echo json_encode([
        'status' => 'error',
        'message' => 'identity_conflict',
        'detail' => scrim_identity_conflict_message($identityConflict['field']),
    ]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO bookings (user_id, scrim_id, status)
    VALUES (?, ?, 'pending')
    ON DUPLICATE KEY UPDATE status = 'pending'");
$stmt->bind_param("ii", $userId, $scrimId);
$stmt->execute();

echo json_encode(['status' => 'success', 'redirect' => '../pages/payment.php?scrim_id=' . $scrimId]);
