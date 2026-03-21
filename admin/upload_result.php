<?php
include_once __DIR__ . "/../includes/app.php";
require_admin();

$scrimId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$scrim = get_scrim_full($conn, $scrimId);
if (!$scrim) {
    header("Location: dashboard.php");
    exit;
}

if (isset($_POST['save_results'])) {
    foreach (($_POST['rank'] ?? []) as $userId => $rankValue) {
        $userId = (int) $userId;
        $rank = (int) $rankValue;
        $kills = (int) ($_POST['kills'][$userId] ?? 0);
        $points = calculate_points($kills, $rank);
        $stmt = $conn->prepare("INSERT INTO results (scrim_id, user_id, kills, rank_position, points, payout_status, payout_transaction_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE kills = VALUES(kills), rank_position = VALUES(rank_position), points = VALUES(points), payout_status = VALUES(payout_status), payout_transaction_id = VALUES(payout_transaction_id)");
        $status = $_POST['payout_status'][$userId] ?? 'unpaid';
        $tx = trim($_POST['payout_transaction_id'][$userId] ?? '');
        $stmt->bind_param("iiiiiss", $scrimId, $userId, $kills, $rank, $points, $status, $tx);
        $stmt->execute();
    }
}

$playersStmt = $conn->prepare("SELECT u.id AS user_id, u.name, u.team_name, b.slot_number, r.kills, r.rank_position, r.payout_status, r.payout_transaction_id
    FROM bookings b
    JOIN users u ON u.id = b.user_id
    LEFT JOIN results r ON r.scrim_id = b.scrim_id AND r.user_id = b.user_id
    WHERE b.scrim_id = ? AND b.status = 'approved'
    ORDER BY b.slot_number ASC, u.name ASC");
$playersStmt->bind_param("i", $scrimId);
$players = fetch_all_assoc($playersStmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Results | BGMI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-screen flex-col bg-slate-950 text-white">
<?php include_once __DIR__ . "/../includes/navbar.php"; ?>
<style>
  .mobile-select-wrap {
    position: relative;
  }

  .mobile-select-wrap::after {
    content: "";
    position: absolute;
    right: 0.9rem;
    top: 50%;
    width: 0.55rem;
    height: 0.55rem;
    border-right: 2px solid #cbd5e1;
    border-bottom: 2px solid #cbd5e1;
    transform: translateY(-65%) rotate(45deg);
    pointer-events: none;
  }

  .mobile-select {
    width: 100%;
    max-width: 100%;
    appearance: none;
    -webkit-appearance: none;
    background-image: none;
    padding-right: 2.3rem;
  }
</style>
<div class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 sm:py-10">
  <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
    <div>
      <h1 class="text-3xl font-black sm:text-4xl">Results & Payouts</h1>
      <p class="mt-2 text-slate-400"><?php echo h($scrim['title']); ?>. Enter kills and rank to auto-generate leaderboard points. Winners can be marked paid or unpaid with transaction IDs.</p>
    </div>
    <a href="../pages/scrim-details.php?id=<?php echo $scrimId; ?>" class="rounded-full bg-slate-900 px-5 py-3">View Public Page</a>
  </div>

  <form method="POST" class="mt-8 space-y-4 md:hidden">
    <?php foreach ($players as $row): ?>
      <article class="rounded-[1.5rem] border border-slate-800 bg-slate-900 p-5">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h2 class="text-lg font-bold"><?php echo h(player_label($row)); ?></h2>
            <p class="mt-1 text-xs text-slate-500">Slot <?php echo $row['slot_number'] ? (int) $row['slot_number'] : '-'; ?></p>
          </div>
        </div>
        <div class="mt-4 grid gap-3">
          <input type="number" name="kills[<?php echo (int) $row['user_id']; ?>]" value="<?php echo (int) ($row['kills'] ?? 0); ?>" placeholder="Kills" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
          <input type="number" name="rank[<?php echo (int) $row['user_id']; ?>]" value="<?php echo (int) ($row['rank_position'] ?? 0); ?>" placeholder="Rank" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
          <label class="mobile-select-wrap block">
            <select name="payout_status[<?php echo (int) $row['user_id']; ?>]" class="mobile-select rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
              <option value="unpaid" <?php echo ($row['payout_status'] ?? 'unpaid') === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
              <option value="paid" <?php echo ($row['payout_status'] ?? '') === 'paid' ? 'selected' : ''; ?>>Paid</option>
            </select>
          </label>
          <input type="text" name="payout_transaction_id[<?php echo (int) $row['user_id']; ?>]" value="<?php echo h($row['payout_transaction_id'] ?? ''); ?>" placeholder="Transaction ID" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm">
        </div>
      </article>
    <?php endforeach; ?>
    <button type="submit" name="save_results" class="w-full rounded-full bg-amber-400 px-6 py-3 font-semibold text-black">Save Results</button>
  </form>

  <form method="POST" class="mt-8 hidden overflow-x-auto rounded-[2rem] border border-slate-800 bg-slate-900 md:block">
    <table class="min-w-full text-left text-sm">
      <thead class="border-b border-slate-800 text-slate-400">
        <tr><th class="px-4 py-4">Slot</th><th class="px-4 py-4">Team</th><th class="px-4 py-4">Kills</th><th class="px-4 py-4">Rank</th><th class="px-4 py-4">Payout</th><th class="px-4 py-4">Transaction ID</th></tr>
      </thead>
      <tbody>
      <?php foreach ($players as $row): ?>
        <tr class="border-b border-slate-900">
          <td class="px-4 py-4"><?php echo $row['slot_number'] ? (int) $row['slot_number'] : '-'; ?></td>
          <td class="px-4 py-4"><?php echo h(player_label($row)); ?></td>
          <td class="px-4 py-4"><input type="number" name="kills[<?php echo (int) $row['user_id']; ?>]" value="<?php echo (int) ($row['kills'] ?? 0); ?>" class="w-24 rounded-xl border border-slate-700 bg-slate-950 px-3 py-2"></td>
          <td class="px-4 py-4"><input type="number" name="rank[<?php echo (int) $row['user_id']; ?>]" value="<?php echo (int) ($row['rank_position'] ?? 0); ?>" class="w-24 rounded-xl border border-slate-700 bg-slate-950 px-3 py-2"></td>
          <td class="px-4 py-4">
            <label class="mobile-select-wrap block min-w-[110px]">
              <select name="payout_status[<?php echo (int) $row['user_id']; ?>]" class="mobile-select rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-sm">
                <option value="unpaid" <?php echo ($row['payout_status'] ?? 'unpaid') === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                <option value="paid" <?php echo ($row['payout_status'] ?? '') === 'paid' ? 'selected' : ''; ?>>Paid</option>
              </select>
            </label>
          </td>
          <td class="px-4 py-4"><input type="text" name="payout_transaction_id[<?php echo (int) $row['user_id']; ?>]" value="<?php echo h($row['payout_transaction_id'] ?? ''); ?>" class="w-40 rounded-xl border border-slate-700 bg-slate-950 px-3 py-2"></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <div class="p-4">
      <button type="submit" name="save_results" class="rounded-full bg-amber-400 px-6 py-3 font-semibold text-black">Save Results</button>
    </div>
  </form>
</div>
<?php include_once __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
