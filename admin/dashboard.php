<?php
include_once __DIR__ . "/../includes/app.php";
require_admin();

$flash = "";
if (isset($_GET['deleted'])) {
    $flash = "Scrim deleted successfully.";
} elseif (isset($_GET['error'])) {
    $flash = "Unable to delete scrim.";
}

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

$recentRows = [];
while ($row = $recent->fetch_assoc()) {
    $recentRows[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard | BGMI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-screen flex-col bg-slate-950 text-white">
<?php include_once __DIR__ . "/../includes/navbar.php"; ?>
<main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 sm:py-10">
  <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
    <div>
      <p class="text-sm uppercase tracking-[0.3em] text-slate-500">Admin Analytics Dashboard</p>
      <h1 class="mt-2 text-3xl font-black sm:text-4xl">Welcome, <?php echo h($_SESSION['name']); ?></h1>
    </div>
    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
      <a href="create_scrim.php" class="rounded-full bg-amber-400 px-5 py-3 text-center font-semibold text-black">Create Scrim</a>
      <a href="payments.php" class="rounded-full bg-slate-900 px-5 py-3 text-center">Review Payments</a>
      <a href="notifications.php" class="rounded-full bg-slate-900 px-5 py-3 text-center">Broadcasts</a>
    </div>
  </div>

  <?php if ($flash !== ""): ?>
    <p class="mt-5 rounded-2xl bg-emerald-500/20 px-4 py-3 text-emerald-200"><?php echo h($flash); ?></p>
  <?php endif; ?>

  <section class="-mx-1 mt-8 flex gap-2 overflow-x-auto pb-1 sm:mx-0 sm:grid sm:grid-cols-2 sm:gap-4 xl:grid-cols-4">
    <div class="min-w-[150px] rounded-2xl border border-slate-800 bg-slate-900 px-4 py-3 sm:min-w-0 sm:rounded-3xl sm:p-6"><p class="text-[11px] uppercase tracking-[0.18em] text-slate-500 sm:text-sm sm:normal-case sm:tracking-normal">Total Scrims</p><p class="mt-1 text-2xl font-bold text-amber-300 sm:mt-2 sm:text-4xl"><?php echo $stats['scrims']; ?></p></div>
    <div class="min-w-[170px] rounded-2xl border border-slate-800 bg-slate-900 px-4 py-3 sm:min-w-0 sm:rounded-3xl sm:p-6"><p class="text-[11px] uppercase tracking-[0.18em] text-slate-500 sm:text-sm sm:normal-case sm:tracking-normal">Open Reg.</p><p class="mt-1 text-2xl font-bold text-emerald-300 sm:mt-2 sm:text-4xl"><?php echo $stats['open']; ?></p></div>
    <div class="min-w-[180px] rounded-2xl border border-slate-800 bg-slate-900 px-4 py-3 sm:min-w-0 sm:rounded-3xl sm:p-6"><p class="text-[11px] uppercase tracking-[0.18em] text-slate-500 sm:text-sm sm:normal-case sm:tracking-normal">Pending Pay.</p><p class="mt-1 text-2xl font-bold text-rose-300 sm:mt-2 sm:text-4xl"><?php echo $stats['pendingPayments']; ?></p></div>
    <div class="min-w-[190px] rounded-2xl border border-slate-800 bg-slate-900 px-4 py-3 sm:min-w-0 sm:rounded-3xl sm:p-6"><p class="text-[11px] uppercase tracking-[0.18em] text-slate-500 sm:text-sm sm:normal-case sm:tracking-normal">Earnings</p><p class="mt-1 text-2xl font-bold text-cyan-300 sm:mt-2 sm:text-4xl"><?php echo format_money($stats['earnings']); ?></p></div>
  </section>

  <section class="mt-8 grid gap-4 md:hidden">
    <?php foreach ($recentRows as $row): ?>
      <article class="rounded-[1.5rem] border border-slate-800 bg-slate-900 p-5">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h2 class="text-lg font-bold"><?php echo h($row['title']); ?></h2>
            <p class="mt-1 text-xs text-slate-500"><?php echo h($row['mode']); ?> | <?php echo h($row['map']); ?></p>
          </div>
          <span class="rounded-full bg-slate-950 px-3 py-1 text-xs text-slate-300"><?php echo strtoupper(h($row['registration_status'])); ?></span>
        </div>
        <div class="mt-4 space-y-2 text-sm text-slate-300">
          <div class="flex justify-between gap-4"><span>Match Time</span><span class="text-right"><?php echo h(date("d M Y, h:i A", strtotime((string) $row['match_time']))); ?></span></div>
          <div class="flex justify-between gap-4"><span>Entry</span><span class="text-right"><?php echo format_money($row['entry_fee']); ?></span></div>
          <div class="flex justify-between gap-4"><span>Prize Pool</span><span class="text-right"><?php echo format_money($row['prize_pool']); ?></span></div>
          <div class="flex justify-between gap-4"><span>Slots</span><span class="text-right"><?php echo (int) $row['approved_slots']; ?>/<?php echo (int) $row['total_slots']; ?></span></div>
        </div>
        <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
          <a href="create_scrim.php?id=<?php echo (int) $row['id']; ?>" class="rounded-2xl bg-slate-950 px-3 py-3 text-center">Edit</a>
          <a href="view_players.php?id=<?php echo (int) $row['id']; ?>" class="rounded-2xl bg-slate-950 px-3 py-3 text-center">Players</a>
          <a href="room_details.php?id=<?php echo (int) $row['id']; ?>" class="rounded-2xl bg-slate-950 px-3 py-3 text-center">Room</a>
          <a href="upload_result.php?id=<?php echo (int) $row['id']; ?>" class="rounded-2xl bg-slate-950 px-3 py-3 text-center">Results</a>
        </div>
        <form action="delete_scrim.php" method="POST" onsubmit="return confirm('Is scrim ko delete karna hai? Iske related bookings, payments aur results bhi remove ho jayenge.');" class="mt-2">
          <input type="hidden" name="scrim_id" value="<?php echo (int) $row['id']; ?>">
          <button type="submit" class="w-full rounded-2xl bg-rose-500/15 px-3 py-3 text-sm font-semibold text-rose-300">Delete Scrim</button>
        </form>
      </article>
    <?php endforeach; ?>
  </section>

  <section class="mt-8 hidden overflow-x-auto rounded-[2rem] border border-slate-800 bg-slate-900 md:block">
    <table class="min-w-full text-left text-sm">
      <thead class="border-b border-slate-800 text-slate-400">
        <tr><th class="px-4 py-4">Scrim</th><th class="px-4 py-4">Match Time</th><th class="px-4 py-4">Entry</th><th class="px-4 py-4">Prize Pool</th><th class="px-4 py-4">Slots</th><th class="px-4 py-4">Status</th><th class="px-4 py-4">Actions</th></tr>
      </thead>
      <tbody>
      <?php foreach ($recentRows as $row): ?>
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
            <div class="flex flex-wrap gap-2 text-xs sm:text-sm">
              <a href="create_scrim.php?id=<?php echo (int) $row['id']; ?>" class="rounded-full bg-slate-950 px-3 py-2">Edit</a>
              <a href="view_players.php?id=<?php echo (int) $row['id']; ?>" class="rounded-full bg-slate-950 px-3 py-2">Players</a>
              <a href="room_details.php?id=<?php echo (int) $row['id']; ?>" class="rounded-full bg-slate-950 px-3 py-2">Room</a>
              <a href="upload_result.php?id=<?php echo (int) $row['id']; ?>" class="rounded-full bg-slate-950 px-3 py-2">Results</a>
              <form action="delete_scrim.php" method="POST" onsubmit="return confirm('Is scrim ko delete karna hai? Iske related bookings, payments aur results bhi remove ho jayenge.');">
                <input type="hidden" name="scrim_id" value="<?php echo (int) $row['id']; ?>">
                <button type="submit" class="rounded-full bg-rose-500/15 px-3 py-2 text-rose-300">Delete</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</main>
<?php include_once __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
