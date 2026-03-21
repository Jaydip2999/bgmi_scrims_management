<?php
include_once __DIR__ . "/../includes/app.php";

$scrimId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$scrim = get_scrim_full($conn, $scrimId);
if (!$scrim) {
    header("Location: scrims.php");
    exit;
}

$prizesStmt = $conn->prepare("SELECT rank_position, percentage, amount FROM prizes WHERE scrim_id = ? ORDER BY rank_position ASC");
$prizesStmt->bind_param("i", $scrimId);
$prizes = fetch_all_assoc($prizesStmt);

$playersStmt = $conn->prepare("SELECT u.name, u.team_name, b.slot_number
    FROM bookings b
    JOIN users u ON u.id = b.user_id
    WHERE b.scrim_id = ? AND b.status = 'approved'
    ORDER BY b.slot_number ASC, u.name ASC");
$playersStmt->bind_param("i", $scrimId);
$players = fetch_all_assoc($playersStmt);

$resultsStmt = $conn->prepare("SELECT r.rank_position, r.kills, r.points, u.name, u.team_name
    FROM results r
    JOIN users u ON u.id = r.user_id
    WHERE r.scrim_id = ?
    ORDER BY r.points DESC, r.rank_position ASC");
$resultsStmt->bind_param("i", $scrimId);
$leaderboard = fetch_all_assoc($resultsStmt);

$booking = null;
if (isset($_SESSION['user_id'])) {
    $bookingStmt = $conn->prepare("SELECT b.*, p.status AS payment_status, p.screenshot
        FROM bookings b
        LEFT JOIN payments p ON p.user_id = b.user_id AND p.scrim_id = b.scrim_id
        WHERE b.user_id = ? AND b.scrim_id = ?
        ORDER BY p.id DESC
        LIMIT 1");
    $bookingStmt->bind_param("ii", $_SESSION['user_id'], $scrimId);
    $bookingStmt->execute();
    $booking = $bookingStmt->get_result()->fetch_assoc();
}

$registrationDeadline = scrim_registration_deadline($scrim);
$matchStarted = strtotime((string) $scrim['match_time']) <= time();
$canJoin = registration_is_open($scrim);
$registrationLabel = $canJoin ? 'Open' : (((int) $scrim['available_slots'] <= 0 || $scrim['registration_status'] === 'full') ? 'Full' : 'Closed');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo h($scrim['title']); ?> | BGMI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-screen flex-col bg-slate-950 text-white">
<?php include_once __DIR__ . "/../includes/navbar.php"; ?>
<main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 sm:py-10">
  <section class="grid gap-6 lg:grid-cols-[1.15fr_.85fr]">
    <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-8">
      <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
          <p class="text-sm uppercase tracking-[0.3em] text-slate-500"><?php echo h($scrim['mode']); ?> | <?php echo h($scrim['map']); ?></p>
          <h1 class="mt-2 text-3xl font-black sm:text-4xl"><?php echo h($scrim['title']); ?></h1>
        </div>
        <span class="rounded-md px-4 py-2 text-sm font-semibold <?php echo $canJoin ? 'bg-emerald-500/20 text-emerald-300' : ($registrationLabel === 'Full' ? 'bg-rose-500/20 text-rose-300' : 'bg-slate-800 text-slate-300'); ?>">
          Registration <?php echo $registrationLabel; ?>
        </span>
      </div>
      <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-xl bg-slate-950 p-4"><p class="text-sm text-slate-500">Entry Fee</p><p class="mt-2 text-xl font-bold text-amber-300 sm:text-2xl"><?php echo format_money($scrim['entry_fee']); ?></p></div>
        <div class="rounded-xl bg-slate-950 p-4"><p class="text-sm text-slate-500">Total Slots</p><p class="mt-2 text-xl font-bold sm:text-2xl"><?php echo (int) $scrim['total_slots']; ?></p></div>
        <div class="rounded-xl bg-slate-950 p-4"><p class="text-sm text-slate-500">Available Slots</p><p class="mt-2 text-xl font-bold text-cyan-300 sm:text-2xl"><?php echo (int) $scrim['available_slots']; ?></p></div>
        <div class="rounded-xl bg-slate-950 p-4"><p class="text-sm text-slate-500">Prize Pool</p><p class="mt-2 text-xl font-bold text-emerald-300 sm:text-2xl"><?php echo format_money($scrim['prize_pool']); ?></p></div>
        <div class="rounded-xl bg-slate-950 p-4"><p class="text-sm text-slate-500">Match Time</p><p class="mt-2 text-base font-bold sm:text-lg"><?php echo h(date("d M Y, h:i A", strtotime((string) $scrim['match_time']))); ?></p></div>
        <div class="rounded-xl bg-slate-950 p-4"><p class="text-sm text-slate-500">Registration Closes</p><p class="mt-2 text-base font-bold text-amber-300 sm:text-lg"><?php echo $registrationDeadline ? h(date("d M, h:i A", $registrationDeadline)) : '-'; ?></p></div>
      </div>
      <div class="mt-4 border border-dashed border-slate-700 bg-slate-950 p-4 text-sm text-slate-400">
        Match countdown: <span id="countdown" class="font-semibold text-amber-300"></span>
      </div>
      <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
        <?php if (!isset($_SESSION['user_id'])): ?>
          <a href="login.php" class="rounded-full bg-amber-400 px-5 py-3 text-center font-semibold text-black">Login to Join</a>
        <?php elseif ($booking): ?>
          <span class="rounded-2xl bg-slate-950 px-5 py-3 text-sm text-slate-300">Your Status: <?php echo strtoupper(h($booking['status'])); ?><?php if (!empty($booking['payment_status'])): ?> | Payment: <?php echo strtoupper(h($booking['payment_status'])); ?><?php endif; ?></span>
        <?php else: ?>
          <a href="payment.php?scrim_id=<?php echo $scrimId; ?>" class="rounded-full px-5 py-3 text-center font-semibold <?php echo $canJoin ? 'bg-amber-400 text-black' : 'cursor-not-allowed bg-slate-800 text-slate-500'; ?>"><?php echo $canJoin ? 'Join Scrim' : 'Joining Disabled'; ?></a>
        <?php endif; ?>
      </div>
      <?php if (!$canJoin && !$booking): ?>
        <p class="mt-4 text-sm text-slate-400"><?php echo $matchStarted ? 'Registration stopped because match time has passed.' : ((int) $scrim['available_slots'] <= 0 || $scrim['registration_status'] === 'full' ? 'Registration stopped because all slots are full.' : ($registrationDeadline && time() >= $registrationDeadline ? 'Registration stopped 10 minutes before match start.' : 'Registration is currently closed by admin.')); ?></p>
      <?php endif; ?>
    </div>

    <div class="space-y-6">
      <section class="rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-6">
        <h2 class="text-xl font-bold">Prize Pool</h2>
        <p class="mt-1 text-sm text-slate-400">Auto-generated from entry fee and total slots, with manual admin override support.</p>
        <div class="mt-5 space-y-3">
          <?php foreach ($prizes as $prize): ?>
            <div class="flex items-center justify-between rounded-xl bg-slate-950 px-4 py-3">
              <span>Rank <?php echo (int) $prize['rank_position']; ?> <span class="text-xs text-slate-500">(<?php echo (float) $prize['percentage']; ?>%)</span></span>
              <strong class="text-emerald-300"><?php echo format_money($prize['amount']); ?></strong>
            </div>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="rounded-2xl border border-slate-800 bg-slate-900 p-6">
        <h2 class="text-xl font-bold">Room Details</h2>
        <?php if (room_visible($scrim)): ?>
          <div class="mt-4 bg-slate-950 p-4">
            <p>Room ID: <span class="font-semibold text-amber-300"><?php echo h($scrim['room_id']); ?></span></p>
            <p class="mt-2">Password: <span class="font-semibold text-amber-300"><?php echo h($scrim['room_password']); ?></span></p>
          </div>
        <?php else: ?>
          <p class="mt-4 rounded-xl bg-slate-950 p-4 text-slate-400">Room will be available soon. It unlocks 10 minutes before the scheduled match time.</p>
        <?php endif; ?>
      </section>
    </div>
  </section>

  <section class="mt-6 grid gap-6 lg:grid-cols-2">
    <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-6">
      <h2 class="text-xl font-bold sm:text-2xl">Registered Teams / Players</h2>
      <div class="mt-4 space-y-3">
        <?php if (!$players): ?><p class="text-slate-400">No approved registrations yet.</p><?php endif; ?>
        <?php foreach ($players as $player): ?>
          <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-950 px-4 py-3">
            <span class="min-w-0 truncate"><?php echo h(player_label($player)); ?></span>
            <span class="shrink-0 text-sm text-slate-400">Slot <?php echo $player['slot_number'] ? (int) $player['slot_number'] : '-'; ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-6">
      <h2 class="text-xl font-bold sm:text-2xl">Rules</h2>
      <div class="mt-4 rounded-xl bg-slate-950 p-4 text-slate-300"><?php echo nl2br(h($scrim['rules_text'] ?: "No extra rules added yet.")); ?></div>
    </div>
  </section>

  <section class="mt-6 rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-6">
    <h2 class="text-xl font-bold sm:text-2xl">Leaderboard</h2>
    <div class="mt-5 grid gap-4 md:grid-cols-3">
      <div class="border border-amber-500/20 bg-amber-500/10 p-4">
        <p class="text-xs uppercase tracking-[0.25em] text-amber-300">Top Rank</p>
        <p class="mt-2 text-3xl font-black"><?php echo $leaderboard ? '#' . (int) $leaderboard[0]['rank_position'] : '--'; ?></p>
      </div>
      <div class="rounded-xl border border-cyan-500/20 bg-cyan-500/10 p-4">
        <p class="text-xs uppercase tracking-[0.25em] text-cyan-300">Top Team</p>
        <p class="mt-2 text-xl font-black"><?php echo $leaderboard ? h(player_label($leaderboard[0])) : '--'; ?></p>
      </div>
      <div class="border border-emerald-500/20 bg-emerald-500/10 p-4">
        <p class="text-xs uppercase tracking-[0.25em] text-emerald-300">Top Points</p>
        <p class="mt-2 text-3xl font-black"><?php echo $leaderboard ? (int) $leaderboard[0]['points'] : '--'; ?></p>
      </div>
    </div>
    <div class="mt-4 overflow-x-auto border border-slate-800">
      <table class="min-w-full text-left text-sm">
        <thead class="bg-slate-950 text-slate-400">
          <tr><th class="px-4 py-3">Rank</th><th class="px-4 py-3">Team Name</th><th class="px-4 py-3">Kills</th><th class="px-4 py-3">Points</th></tr>
        </thead>
        <tbody>
        <?php if (!$leaderboard): ?>
          <tr><td colspan="4" class="px-4 py-4 text-slate-400">Leaderboard will appear after results are uploaded.</td></tr>
        <?php endif; ?>
        <?php foreach ($leaderboard as $row): ?>
          <tr class="border-b border-slate-900 odd:bg-slate-900 even:bg-slate-950">
            <td class="px-4 py-3 font-bold <?php echo (int) $row['rank_position'] <= 3 ? 'text-amber-300' : 'text-white'; ?>">#<?php echo (int) $row['rank_position']; ?></td>
            <td class="px-4 py-3"><?php echo h(player_label($row)); ?></td>
            <td class="px-4 py-3"><span class="rounded-md bg-slate-800 px-3 py-1"><?php echo (int) $row['kills']; ?></span></td>
            <td class="px-4 py-3"><span class="rounded-md bg-emerald-500/15 px-3 py-1 font-semibold text-emerald-300"><?php echo (int) $row['points']; ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>
<?php include_once __DIR__ . "/../includes/footer.php"; ?>
<script>
const countdown = document.getElementById("countdown");
const target = new Date("<?php echo h((string) $scrim['match_time']); ?>").getTime();
function updateCountdown() {
  const diff = target - Date.now();
  if (diff <= 0) {
    countdown.textContent = "Match started";
    return;
  }
  const hours = Math.floor(diff / 3600000);
  const minutes = Math.floor((diff % 3600000) / 60000);
  const seconds = Math.floor((diff % 60000) / 1000);
  countdown.textContent = `${hours}h ${minutes}m ${seconds}s`;
}
updateCountdown();
setInterval(updateCountdown, 1000);
</script>
</body>
</html>
