<?php
include_once __DIR__ . "/../includes/app.php";
require_admin();

$scrimId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$scrim = get_scrim_full($conn, $scrimId);
if (!$scrim) {
    header("Location: dashboard.php");
    exit;
}

if (isset($_GET['action'], $_GET['booking'])) {
    $action = $_GET['action'];
    $bookingId = (int) $_GET['booking'];
    $bookingStmt = $conn->prepare("SELECT * FROM bookings WHERE id = ? AND scrim_id = ?");
    $bookingStmt->bind_param("ii", $bookingId, $scrimId);
    $bookingStmt->execute();
    $booking = $bookingStmt->get_result()->fetch_assoc();

    if ($booking) {
        if ($action === 'approve') {
            $slot = get_next_slot($conn, $scrimId);
            $stmt = $conn->prepare("UPDATE bookings SET status = 'approved', slot_number = ? WHERE id = ?");
            $stmt->bind_param("ii", $slot, $bookingId);
            $stmt->execute();
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE bookings SET status = 'rejected', slot_number = NULL WHERE id = ?");
            $stmt->bind_param("i", $bookingId);
            $stmt->execute();
        } elseif ($action === 'remove') {
            $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled', slot_number = NULL WHERE id = ?");
            $stmt->bind_param("i", $bookingId);
            $stmt->execute();
        } elseif ($action === 'ban') {
            $ban = $conn->prepare("UPDATE users SET is_banned = 1 WHERE id = ?");
            $ban->bind_param("i", $booking['user_id']);
            $ban->execute();
            $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled', slot_number = NULL WHERE user_id = ? AND scrim_id = ?");
            $stmt->bind_param("ii", $booking['user_id'], $scrimId);
            $stmt->execute();
        }
        sync_scrim_meta($conn, $scrimId);
    }
    header("Location: view_players.php?id=" . $scrimId);
    exit;
}

$rows = $conn->prepare("SELECT b.id AS booking_id, b.status, b.slot_number, u.id AS user_id, u.name, u.email, u.team_name, u.is_banned
    FROM bookings b
    JOIN users u ON u.id = b.user_id
    WHERE b.scrim_id = ?
    ORDER BY FIELD(b.status, 'approved', 'pending', 'rejected', 'cancelled'), b.slot_number ASC, u.name ASC");
$rows->bind_param("i", $scrimId);
$players = fetch_all_assoc($rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Registrations | BGMI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-white">
<?php include_once __DIR__ . "/../includes/navbar.php"; ?>
<div class="mx-auto max-w-7xl px-6 py-10">
  <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
    <div>
      <h1 class="text-4xl font-black"><?php echo h($scrim['title']); ?> Registrations</h1>
      <p class="mt-2 text-slate-400">Approve, reject, replace, remove, or ban teams and players. Approved users get the next available slot automatically.</p>
    </div>
    <a href="dashboard.php" class="rounded-full bg-slate-900 px-5 py-3">Back</a>
  </div>

  <div class="mt-8 overflow-x-auto rounded-[2rem] border border-slate-800 bg-slate-900">
    <table class="min-w-full text-left text-sm">
      <thead class="border-b border-slate-800 text-slate-400">
        <tr><th class="px-4 py-4">Slot</th><th class="px-4 py-4">Player / Team</th><th class="px-4 py-4">Email</th><th class="px-4 py-4">Status</th><th class="px-4 py-4">Action</th></tr>
      </thead>
      <tbody>
      <?php foreach ($players as $row): ?>
        <tr class="border-b border-slate-900">
          <td class="px-4 py-4"><?php echo $row['slot_number'] ? (int) $row['slot_number'] : '-'; ?></td>
          <td class="px-4 py-4"><?php echo h(player_label($row)); ?><?php if ((int) $row['is_banned'] === 1): ?><div class="text-xs text-rose-300">Banned</div><?php endif; ?></td>
          <td class="px-4 py-4"><?php echo h($row['email']); ?></td>
          <td class="px-4 py-4"><?php echo strtoupper(h($row['status'])); ?></td>
          <td class="px-4 py-4">
            <div class="flex flex-wrap gap-3">
              <a href="?id=<?php echo $scrimId; ?>&action=approve&booking=<?php echo (int) $row['booking_id']; ?>" class="text-emerald-300">Approve</a>
              <a href="?id=<?php echo $scrimId; ?>&action=reject&booking=<?php echo (int) $row['booking_id']; ?>" class="text-amber-300">Reject</a>
              <a href="?id=<?php echo $scrimId; ?>&action=remove&booking=<?php echo (int) $row['booking_id']; ?>" class="text-slate-300">Remove</a>
              <a href="?id=<?php echo $scrimId; ?>&action=ban&booking=<?php echo (int) $row['booking_id']; ?>" class="text-rose-300">Ban</a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include_once __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
