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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>History | BGMI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-white">
<?php include_once __DIR__ . "/../includes/navbar.php"; ?>
<div class="mx-auto max-w-6xl px-6 py-10">
  <h1 class="text-4xl font-black">Match History</h1>
  <div class="mt-8 overflow-x-auto rounded-[2rem] border border-slate-800 bg-slate-900">
    <table class="min-w-full text-left text-sm">
      <thead class="border-b border-slate-800 text-slate-400">
        <tr><th class="px-4 py-4">Match</th><th class="px-4 py-4">Kills</th><th class="px-4 py-4">Rank</th><th class="px-4 py-4">Points</th><th class="px-4 py-4">Payout</th></tr>
      </thead>
      <tbody>
      <?php foreach ($history as $row): ?>
        <tr class="border-b border-slate-900">
          <td class="px-4 py-4"><?php echo h($row['title']); ?><div class="text-xs text-slate-500"><?php echo h(date("d M Y, h:i A", strtotime((string) $row['match_time']))); ?></div></td>
          <td class="px-4 py-4"><?php echo (int) $row['kills']; ?></td>
          <td class="px-4 py-4">#<?php echo (int) $row['rank_position']; ?></td>
          <td class="px-4 py-4"><?php echo (int) $row['points']; ?></td>
          <td class="px-4 py-4"><?php echo strtoupper(h($row['payout_status'])); ?><?php if ($row['payout_transaction_id']): ?><div class="text-xs text-slate-500"><?php echo h($row['payout_transaction_id']); ?></div><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include_once __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
