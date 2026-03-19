<?php
include_once __DIR__ . "/../includes/app.php";

$rows = [];
$board = $conn->query("SELECT u.name, u.team_name, SUM(r.points) AS total_points, SUM(r.kills) AS total_kills, COUNT(r.id) AS matches_played
    FROM results r
    JOIN users u ON u.id = r.user_id
    GROUP BY r.user_id
    ORDER BY total_points DESC, total_kills DESC
    LIMIT 50");
while ($row = $board->fetch_assoc()) {
    $rows[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Leaderboard | BGMI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-white">
<?php include_once __DIR__ . "/../includes/navbar.php"; ?>
<div class="mx-auto max-w-6xl px-6 py-10">
  <h1 class="text-4xl font-black">Global Leaderboard</h1>
  <p class="mt-2 text-slate-400">Top performers across all finished BGMI scrims.</p>

  <div class="mt-8 grid gap-4 md:grid-cols-3">
    <div class="border border-amber-500/20 bg-amber-500/10 p-5">
      <p class="text-xs uppercase tracking-[0.3em] text-amber-300">Rank 1</p>
      <p class="mt-3 text-2xl font-black"><?php echo isset($rows[0]) ? h(player_label($rows[0])) : '--'; ?></p>
      <p class="mt-2 text-sm text-amber-200"><?php echo isset($rows[0]) ? (int) $rows[0]['total_points'] . ' pts' : ''; ?></p>
    </div>
    <div class="rounded-xl border border-cyan-500/20 bg-cyan-500/10 p-5">
      <p class="text-xs uppercase tracking-[0.3em] text-cyan-300">Most Kills</p>
      <p class="mt-3 text-2xl font-black"><?php echo $rows ? max(array_map(fn($item) => (int) $item['total_kills'], $rows)) : 0; ?></p>
      <p class="mt-2 text-sm text-cyan-200">Total kills by a single player/team</p>
    </div>
    <div class="border border-emerald-500/20 bg-emerald-500/10 p-5">
      <p class="text-xs uppercase tracking-[0.3em] text-emerald-300">Tracked Competitors</p>
      <p class="mt-3 text-2xl font-black"><?php echo count($rows); ?></p>
      <p class="mt-2 text-sm text-emerald-200">Leaderboard entries with results</p>
    </div>
  </div>

  <div class="mt-8 overflow-x-auto border border-slate-800 bg-slate-900">
    <table class="min-w-full text-left text-sm">
      <thead class="bg-slate-950 text-slate-400">
        <tr><th class="px-4 py-4">Rank</th><th class="px-4 py-4">Player / Team</th><th class="px-4 py-4">Points</th><th class="px-4 py-4">Kills</th><th class="px-4 py-4">Matches</th></tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $index => $row): ?>
        <tr class="border-b border-slate-900 odd:bg-slate-900 even:bg-slate-950">
          <td class="px-4 py-4">
            <span class="inline-flex min-w-12 items-center justify-center rounded-md px-3 py-1 font-bold <?php echo $index < 3 ? 'bg-amber-500/15 text-amber-300' : 'bg-slate-800 text-white'; ?>">
              #<?php echo $index + 1; ?>
            </span>
          </td>
          <td class="px-4 py-4">
            <div class="font-semibold"><?php echo h(player_label($row)); ?></div>
            <div class="text-xs text-slate-500"><?php echo (int) $row['matches_played']; ?> matches played</div>
          </td>
          <td class="px-4 py-4"><span class="rounded-md bg-emerald-500/15 px-3 py-1 font-semibold text-emerald-300"><?php echo (int) $row['total_points']; ?></span></td>
          <td class="px-4 py-4"><span class="rounded-md bg-slate-800 px-3 py-1"><?php echo (int) $row['total_kills']; ?></span></td>
          <td class="px-4 py-4"><?php echo (int) $row['matches_played']; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include_once __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
