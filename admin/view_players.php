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

$rows = $conn->prepare("SELECT b.id AS booking_id, b.status, b.slot_number, u.id AS user_id, u.name, u.email, u.phone, u.bgmi_uid, u.team_name, u.is_banned
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
<body class="flex min-h-screen flex-col bg-slate-950 text-white">
<?php include_once __DIR__ . "/../includes/navbar.php"; ?>
<div class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 sm:py-10">
  <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
    <div>
      <h1 class="text-3xl font-black sm:text-4xl"><?php echo h($scrim['title']); ?> Registrations</h1>
      <p class="mt-2 text-slate-400">Approve, reject, replace, remove, or ban teams and players. Approved users get the next available slot automatically.</p>
    </div>
    <a href="dashboard.php" class="rounded-full bg-slate-900 px-5 py-3">Back</a>
  </div>

  <div class="mt-8 grid gap-4 md:hidden">
    <?php foreach ($players as $row): ?>
      <?php
        $playerMessage = "Hello " . player_label($row) . ", regarding your registration for " . $scrim['title'] . ".";
        $playerWhatsApp = whatsapp_link($row['phone'], $playerMessage);
        $playerMail = mailto_link($row['email'], 'BGMI Scrim Registration Update', 'Hello ' . player_label($row) . ',');
      ?>
      <article class="rounded-[1.5rem] border border-slate-800 bg-slate-900 p-5">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h2 class="text-lg font-bold"><?php echo h(player_label($row)); ?></h2>
            <?php if ((int) $row['is_banned'] === 1): ?><div class="mt-1 text-xs text-rose-300">Banned</div><?php endif; ?>
          </div>
          <span class="rounded-full bg-slate-950 px-3 py-1 text-xs text-slate-300"><?php echo strtoupper(h($row['status'])); ?></span>
        </div>
        <div class="mt-4 space-y-2 text-sm text-slate-300">
          <div class="flex justify-between gap-4"><span>Slot</span><span><?php echo $row['slot_number'] ? (int) $row['slot_number'] : '-'; ?></span></div>
          <div class="flex justify-between gap-4"><span>Email</span><span class="text-right"><?php echo h($row['email'] ?: '-'); ?></span></div>
          <div class="flex justify-between gap-4"><span>Phone</span><span class="text-right"><?php echo h($row['phone'] ?: '-'); ?></span></div>
          <div class="flex justify-between gap-4"><span>BGMI UID</span><span class="text-right"><?php echo h($row['bgmi_uid'] ?: '-'); ?></span></div>
        </div>
        <div class="mt-4 flex flex-wrap gap-3 text-sm">
          <?php if ($playerWhatsApp): ?><a href="<?php echo h($playerWhatsApp); ?>" target="_blank" class="text-emerald-300">WhatsApp</a><?php endif; ?>
          <?php if ($playerMail): ?><a href="<?php echo h($playerMail); ?>" class="text-sky-300">Email</a><?php endif; ?>
        </div>
        <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
          <a href="?id=<?php echo $scrimId; ?>&action=approve&booking=<?php echo (int) $row['booking_id']; ?>" class="rounded-2xl bg-emerald-500/15 px-3 py-3 text-center text-emerald-300">Approve</a>
          <a href="?id=<?php echo $scrimId; ?>&action=reject&booking=<?php echo (int) $row['booking_id']; ?>" class="rounded-2xl bg-amber-400/10 px-3 py-3 text-center text-amber-300">Reject</a>
          <a href="?id=<?php echo $scrimId; ?>&action=remove&booking=<?php echo (int) $row['booking_id']; ?>" class="rounded-2xl bg-slate-950 px-3 py-3 text-center text-slate-300">Remove</a>
          <a href="?id=<?php echo $scrimId; ?>&action=ban&booking=<?php echo (int) $row['booking_id']; ?>" class="rounded-2xl bg-rose-500/15 px-3 py-3 text-center text-rose-300">Ban</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <div class="mt-8 hidden overflow-x-auto rounded-[2rem] border border-slate-800 bg-slate-900 md:block">
    <table class="min-w-full text-left text-sm">
      <thead class="border-b border-slate-800 text-slate-400">
        <tr><th class="px-4 py-4">Slot</th><th class="px-4 py-4">Player / Team</th><th class="px-4 py-4">Contact</th><th class="px-4 py-4">Identity</th><th class="px-4 py-4">Status</th><th class="px-4 py-4">Action</th></tr>
      </thead>
      <tbody>
      <?php foreach ($players as $row): ?>
        <?php
          $playerMessage = "Hello " . player_label($row) . ", regarding your registration for " . $scrim['title'] . ".";
          $playerWhatsApp = whatsapp_link($row['phone'], $playerMessage);
          $playerMail = mailto_link($row['email'], 'BGMI Scrim Registration Update', 'Hello ' . player_label($row) . ',');
        ?>
        <tr class="border-b border-slate-900">
          <td class="px-4 py-4"><?php echo $row['slot_number'] ? (int) $row['slot_number'] : '-'; ?></td>
          <td class="px-4 py-4"><?php echo h(player_label($row)); ?><?php if ((int) $row['is_banned'] === 1): ?><div class="text-xs text-rose-300">Banned</div><?php endif; ?></td>
          <td class="px-4 py-4">
            <div class="space-y-1">
              <div class="text-sm"><?php echo h($row['email'] ?: '-'); ?></div>
              <div class="flex flex-wrap gap-3 text-xs">
                <?php if ($playerWhatsApp): ?><a href="<?php echo h($playerWhatsApp); ?>" target="_blank" class="text-emerald-300">WhatsApp</a><?php endif; ?>
                <?php if ($playerMail): ?><a href="<?php echo h($playerMail); ?>" class="text-sky-300">Email</a><?php endif; ?>
              </div>
            </div>
          </td>
          <td class="px-4 py-4">
            <div class="text-sm text-slate-300">Phone: <?php echo h($row['phone'] ?: '-'); ?></div>
            <div class="text-xs text-slate-500">BGMI UID: <?php echo h($row['bgmi_uid'] ?: '-'); ?></div>
          </td>
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
