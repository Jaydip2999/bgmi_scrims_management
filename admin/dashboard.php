<?php
include_once __DIR__ . "/../includes/app.php";
require_admin();

$stats = [
    'scrims' => (int) $conn->query("SELECT COUNT(*) AS total FROM scrims")->fetch_assoc()['total'],
    'open' => (int) $conn->query("SELECT COUNT(*) AS total FROM scrims WHERE registration_status='open'")->fetch_assoc()['total'],
    'pendingPayments' => (int) $conn->query("SELECT COUNT(*) AS total FROM payments WHERE status='pending'")->fetch_assoc()['total'],
    'earnings' => (float) $conn->query("SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE status='approved'")->fetch_assoc()['total'],
];

$recent = $conn->query("SELECT s.*,
    (SELECT COUNT(*) FROM bookings b WHERE b.scrim_id=s.id AND b.status='approved') AS approved_slots
    FROM scrims s
    ORDER BY s.match_time DESC
    LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard | BGMI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-white">
<?php include_once __DIR__ . "/../includes/navbar.php"; ?>
<main class="mx-auto max-w-7xl px-6 py-10">
  <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
    <div>
      <p class="text-sm uppercase tracking-[0.3em] text-slate-500">Admin Analytics Dashboard</p>
      <h1 class="mt-2 text-4xl font-black">Welcome, <?php echo h($_SESSION['name']); ?></h1>
    </div>
    <div class="flex flex-wrap gap-3">
      <a href="create_scrim.php" class="rounded-full bg-amber-400 px-5 py-3 font-semibold text-black">Create Scrim</a>
      <a href="payments.php" class="rounded-full bg-slate-900 px-5 py-3">Review Payments</a>
      <a href="notifications.php" class="rounded-full bg-slate-900 px-5 py-3">Broadcasts</a>
    </div>
  </div>

  <section class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-3xl border border-slate-800 bg-slate-900 p-6"><p class="text-sm text-slate-500">Total Scrims</p><p class="mt-2 text-4xl font-bold text-amber-300"><?php echo $stats['scrims']; ?></p></div>
    <div class="rounded-3xl border border-slate-800 bg-slate-900 p-6"><p class="text-sm text-slate-500">Open Registrations</p><p class="mt-2 text-4xl font-bold text-emerald-300"><?php echo $stats['open']; ?></p></div>
    <div class="rounded-3xl border border-slate-800 bg-slate-900 p-6"><p class="text-sm text-slate-500">Pending Payments</p><p class="mt-2 text-4xl font-bold text-rose-300"><?php echo $stats['pendingPayments']; ?></p></div>
    <div class="rounded-3xl border border-slate-800 bg-slate-900 p-6"><p class="text-sm text-slate-500">Approved Earnings</p><p class="mt-2 text-4xl font-bold text-cyan-300"><?php echo format_money($stats['earnings']); ?></p></div>
  </section>

  <section class="mt-8 overflow-x-auto rounded-[2rem] border border-slate-800 bg-slate-900">
    <table class="min-w-full text-left text-sm">
      <thead class="border-b border-slate-800 text-slate-400">
        <tr><th class="px-4 py-4">Scrim</th><th class="px-4 py-4">Match Time</th><th class="px-4 py-4">Entry</th><th class="px-4 py-4">Prize Pool</th><th class="px-4 py-4">Slots</th><th class="px-4 py-4">Status</th><th class="px-4 py-4">Actions</th></tr>
      </thead>
      <tbody>
      <?php while ($row = $recent->fetch_assoc()): ?>
        <tr class="border-b border-slate-900">
          <td class="px-4 py-4">
            <div class="font-semibold"><?php echo h($row['title']); ?></div>
            <div class="text-xs text-slate-500"><?php echo h($row['mode']); ?> | <?php echo h($row['map']); ?></div>
          </td>
          <td class="px-4 py-4"><?php echo h(date("d M Y, h:i A", strtotime((string) $row['match_time']))); ?></td>
          <td class="px-4 py-4"><?php echo format_money($row['entry_fee']); ?></td>
          <td class="px-4 py-4"><?php echo format_money($row['prize_pool']); ?></td>
          <td class="px-4 py-4"><?php echo (int) $row['approved_slots']; ?>/<?php echo (int) $row['total_slots']; ?></td>
          <td class="px-4 py-4"><?php echo strtoupper(h($row['registration_status'])); ?></td>
          <td class="px-4 py-4">
            <div class="flex flex-wrap gap-2">
              <a href="create_scrim.php?id=<?php echo (int) $row['id']; ?>" class="rounded-full bg-slate-950 px-3 py-2">Edit</a>
              <a href="view_players.php?id=<?php echo (int) $row['id']; ?>" class="rounded-full bg-slate-950 px-3 py-2">Players</a>
              <a href="room_details.php?id=<?php echo (int) $row['id']; ?>" class="rounded-full bg-slate-950 px-3 py-2">Room</a>
              <a href="upload_result.php?id=<?php echo (int) $row['id']; ?>" class="rounded-full bg-slate-950 px-3 py-2">Results</a>
            </div>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </section>
</main>
<?php include_once __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
