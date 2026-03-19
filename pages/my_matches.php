<?php
include_once __DIR__ . "/../includes/app.php";
require_login();

$filter = $_GET['filter'] ?? 'all';
$allowedFilters = ['all', 'upcoming', 'completed'];
if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}

$sql = "SELECT s.*, b.status, b.slot_number, MAX(p.status) AS payment_status
    FROM bookings b
    JOIN scrims s ON s.id = b.scrim_id
    LEFT JOIN payments p ON p.user_id = b.user_id AND p.scrim_id = b.scrim_id
    WHERE b.user_id = ?";
if ($filter === 'upcoming') {
    $sql .= " AND s.match_time >= NOW()";
} elseif ($filter === 'completed') {
    $sql .= " AND s.match_time < NOW()";
}
$sql .= "
    GROUP BY b.id, s.id
    ORDER BY s.match_time DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$rows = fetch_all_assoc($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Matches | BGMI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-white">
<?php include_once __DIR__ . "/../includes/navbar.php"; ?>
<div class="mx-auto max-w-7xl px-6 py-10">
  <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
    <h1 class="text-4xl font-black">My Match Registrations</h1>
    <div class="flex flex-wrap gap-2">
      <?php foreach ($allowedFilters as $tab): ?>
        <a href="?filter=<?php echo $tab; ?>" class="rounded-2xl px-4 py-2 text-sm <?php echo $filter === $tab ? 'bg-amber-400 font-semibold text-black' : 'bg-slate-900 text-slate-300'; ?>">
          <?php echo ucfirst($tab); ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="mt-8 grid gap-6 lg:grid-cols-2">
    <?php if (!$rows): ?>
      <div class="rounded-2xl border border-slate-800 bg-slate-900 p-6 text-slate-400">No matches found for this filter.</div>
    <?php endif; ?>
    <?php foreach ($rows as $row): ?>
      <article class="rounded-2xl border border-slate-800 bg-slate-900 p-6">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h2 class="text-2xl font-bold"><?php echo h($row['title']); ?></h2>
            <p class="mt-1 text-slate-400"><?php echo h(date("d M Y, h:i A", strtotime((string) $row['match_time']))); ?></p>
          </div>
          <a href="scrim-details.php?id=<?php echo (int) $row['id']; ?>" class="rounded-full bg-slate-950 px-4 py-2 text-sm">Details</a>
        </div>
        <div class="mt-5 grid gap-3 text-sm text-slate-300">
          <div class="flex justify-between"><span>Type</span><span><?php echo strtotime((string) $row['match_time']) < time() ? 'COMPLETED' : 'UPCOMING'; ?></span></div>
          <div class="flex justify-between"><span>Booking Status</span><span><?php echo strtoupper(h($row['status'])); ?></span></div>
          <div class="flex justify-between"><span>Payment</span><span><?php echo strtoupper(h($row['payment_status'] ?: 'pending')); ?></span></div>
          <div class="flex justify-between"><span>Slot Number</span><span><?php echo $row['slot_number'] ? (int) $row['slot_number'] : '-'; ?></span></div>
        </div>
        <div class="mt-5 rounded-2xl bg-slate-950 p-4">
          <?php if ($row['status'] === 'approved' && room_visible($row)): ?>
            <p>Room ID: <span class="font-semibold text-amber-300"><?php echo h($row['room_id']); ?></span></p>
            <p class="mt-2">Password: <span class="font-semibold text-amber-300"><?php echo h($row['room_password']); ?></span></p>
          <?php else: ?>
            <p class="text-slate-400">Room details appear 10 minutes before match time after approval.</p>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</div>
<?php include_once __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
