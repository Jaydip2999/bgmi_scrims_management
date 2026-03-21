<?php
include_once __DIR__ . "/../includes/app.php";
require_login();

$stmt = $conn->prepare("SELECT s.title, s.match_time, r.kills, r.rank_position, r.points, r.payout_status, r.payout_transaction_id
    FROM results r
    JOIN scrims s ON s.id = r.scrim_id
    WHERE r.user_id = ?
    ORDER BY s.match_time DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$history = fetch_all_assoc($stmt);

$totalMatches = count($history);
$totalKills = 0;
$totalPoints = 0;
$bestRank = null;
$paidCount = 0;

foreach ($history as $row) {
    $totalKills += (int) $row['kills'];
    $totalPoints += (int) $row['points'];
    $rank = (int) $row['rank_position'];
    if ($bestRank === null || $rank < $bestRank) {
        $bestRank = $rank;
    }
    if (($row['payout_status'] ?? '') === 'paid') {
        $paidCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>History | BGMI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-screen flex-col bg-slate-950 text-white">
<?php include_once __DIR__ . "/../includes/navbar.php"; ?>
<main class="mx-auto w-full max-w-7xl flex-1 px-6 py-10">
  <section class="rounded-[2rem] border border-slate-800 bg-[radial-gradient(circle_at_top_left,_rgba(245,158,11,0.12),_transparent_32%),linear-gradient(135deg,#0f172a,#111827)] p-8">
    <p class="text-sm uppercase tracking-[0.3em] text-slate-500">Player Record</p>
    <h1 class="mt-3 text-4xl font-black">Match History</h1>
    <p class="mt-3 max-w-2xl text-sm text-slate-400">Yahan tumhare completed matches, earned points, payout status aur recent performance ek clean view me milenge.</p>

    <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-3xl border border-slate-800 bg-slate-950/70 p-5">
        <p class="text-sm text-slate-500">Matches Played</p>
        <p class="mt-2 text-3xl font-black text-white"><?php echo $totalMatches; ?></p>
      </div>
      <div class="rounded-3xl border border-slate-800 bg-slate-950/70 p-5">
        <p class="text-sm text-slate-500">Total Kills</p>
        <p class="mt-2 text-3xl font-black text-amber-300"><?php echo $totalKills; ?></p>
      </div>
      <div class="rounded-3xl border border-slate-800 bg-slate-950/70 p-5">
        <p class="text-sm text-slate-500">Total Points</p>
        <p class="mt-2 text-3xl font-black text-emerald-300"><?php echo $totalPoints; ?></p>
      </div>
      <div class="rounded-3xl border border-slate-800 bg-slate-950/70 p-5">
        <p class="text-sm text-slate-500">Best Rank</p>
        <p class="mt-2 text-3xl font-black text-sky-300"><?php echo $bestRank !== null ? '#' . $bestRank : '-'; ?></p>
      </div>
    </div>
  </section>

  <?php if (!$history): ?>
    <section class="mt-8 flex min-h-[42vh] items-center justify-center rounded-[2rem] border border-dashed border-slate-800 bg-slate-900/70 p-8 text-center">
      <div>
        <h2 class="text-2xl font-bold">No Match History Yet</h2>
        <p class="mt-3 text-sm text-slate-400">Jab tumhare completed scrims me result upload hoga, to yahan kills, rank, points aur payout details dikhengi.</p>
        <a href="scrims.php" class="mt-6 inline-flex rounded-full bg-amber-400 px-5 py-3 font-semibold text-black">Browse Scrims</a>
      </div>
    </section>
  <?php else: ?>
    <section class="mt-8 grid gap-6 xl:grid-cols-[1.2fr_.8fr]">
      <div class="overflow-hidden rounded-[2rem] border border-slate-800 bg-slate-900">
        <div class="flex items-center justify-between border-b border-slate-800 px-6 py-5">
          <div>
            <h2 class="text-2xl font-bold">Performance Timeline</h2>
            <p class="mt-1 text-sm text-slate-400">Recent completed scrims with score and payout status.</p>
          </div>
          <span class="rounded-full bg-slate-950 px-4 py-2 text-sm text-slate-300"><?php echo $paidCount; ?> Paid</span>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full text-left text-sm">
            <thead class="border-b border-slate-800 text-slate-400">
              <tr>
                <th class="px-6 py-4">Match</th>
                <th class="px-6 py-4">Kills</th>
                <th class="px-6 py-4">Rank</th>
                <th class="px-6 py-4">Points</th>
                <th class="px-6 py-4">Payout</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($history as $row): ?>
              <?php $paid = ($row['payout_status'] ?? '') === 'paid'; ?>
              <tr class="border-b border-slate-800/70 align-top">
                <td class="px-6 py-5">
                  <div class="font-semibold text-white"><?php echo h($row['title']); ?></div>
                  <div class="mt-1 text-xs text-slate-500"><?php echo h(date("d M Y, h:i A", strtotime((string) $row['match_time']))); ?></div>
                </td>
                <td class="px-6 py-5 font-semibold text-amber-300"><?php echo (int) $row['kills']; ?></td>
                <td class="px-6 py-5 font-semibold text-sky-300">#<?php echo (int) $row['rank_position']; ?></td>
                <td class="px-6 py-5 font-semibold text-emerald-300"><?php echo (int) $row['points']; ?></td>
                <td class="px-6 py-5">
                  <span class="rounded-full px-3 py-1 text-xs font-semibold <?php echo $paid ? 'bg-emerald-500/15 text-emerald-300' : 'bg-amber-400/10 text-amber-300'; ?>">
                    <?php echo strtoupper(h($row['payout_status'])); ?>
                  </span>
                  <?php if ($row['payout_transaction_id']): ?>
                    <div class="mt-2 text-xs text-slate-500">Txn: <?php echo h($row['payout_transaction_id']); ?></div>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <aside class="space-y-6">
        <div class="rounded-[2rem] border border-slate-800 bg-slate-900 p-6">
          <h2 class="text-2xl font-bold">Quick Snapshot</h2>
          <div class="mt-5 space-y-4 text-sm text-slate-300">
            <div class="flex justify-between"><span>Payouts Received</span><span class="font-semibold text-emerald-300"><?php echo $paidCount; ?></span></div>
            <div class="flex justify-between"><span>Unpaid Results</span><span class="font-semibold text-amber-300"><?php echo max(0, $totalMatches - $paidCount); ?></span></div>
            <div class="flex justify-between"><span>Average Points</span><span class="font-semibold text-white"><?php echo $totalMatches > 0 ? number_format($totalPoints / $totalMatches, 1) : '0'; ?></span></div>
            <div class="flex justify-between"><span>Average Kills</span><span class="font-semibold text-white"><?php echo $totalMatches > 0 ? number_format($totalKills / $totalMatches, 1) : '0'; ?></span></div>
          </div>
        </div>

        <div class="rounded-[2rem] border border-slate-800 bg-slate-900 p-6">
          <h2 class="text-2xl font-bold">Recent Results</h2>
          <div class="mt-5 space-y-4">
            <?php foreach (array_slice($history, 0, 3) as $row): ?>
              <div class="rounded-2xl bg-slate-950 p-4">
                <div class="flex items-start justify-between gap-4">
                  <div>
                    <p class="font-semibold text-white"><?php echo h($row['title']); ?></p>
                    <p class="mt-1 text-xs text-slate-500"><?php echo h(date("d M Y, h:i A", strtotime((string) $row['match_time']))); ?></p>
                  </div>
                  <span class="rounded-full bg-slate-900 px-3 py-1 text-xs text-slate-300">#<?php echo (int) $row['rank_position']; ?></span>
                </div>
                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                  <span class="rounded-full bg-amber-400/10 px-3 py-1 text-amber-300"><?php echo (int) $row['kills']; ?> Kills</span>
                  <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-emerald-300"><?php echo (int) $row['points']; ?> Points</span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </aside>
    </section>
  <?php endif; ?>
</main>
<?php include_once __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
