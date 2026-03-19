<?php
include_once __DIR__ . "/../includes/app.php";
require_admin();

$payments = $conn->query("SELECT p.*, u.name, u.team_name, s.title
    FROM payments p
    JOIN users u ON u.id = p.user_id
    JOIN scrims s ON s.id = p.scrim_id
    ORDER BY p.created_at DESC");

$summary = [
    'pending' => (int) $conn->query("SELECT COUNT(*) AS total FROM payments WHERE status='pending'")->fetch_assoc()['total'],
    'approved' => (int) $conn->query("SELECT COUNT(*) AS total FROM payments WHERE status='approved'")->fetch_assoc()['total'],
    'rejected' => (int) $conn->query("SELECT COUNT(*) AS total FROM payments WHERE status='rejected'")->fetch_assoc()['total'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payments | BGMI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-white">
<?php include_once __DIR__ . "/../includes/navbar.php"; ?>
<div class="mx-auto max-w-7xl px-6 py-10">
  <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
    <div>
      <h1 class="text-4xl font-black">Payment Requests</h1>
      <p class="mt-2 text-slate-400">Status badges are highlighted so pending approvals are immediately visible.</p>
    </div>
  </div>
  <div class="mt-8 grid gap-4 md:grid-cols-3">
    <div class="rounded-2xl border border-amber-500/20 bg-amber-500/10 p-5"><p class="text-sm text-amber-200">Pending</p><p class="mt-2 text-3xl font-black text-amber-300"><?php echo $summary['pending']; ?></p></div>
    <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-5"><p class="text-sm text-emerald-200">Approved</p><p class="mt-2 text-3xl font-black text-emerald-300"><?php echo $summary['approved']; ?></p></div>
    <div class="rounded-2xl border border-rose-500/20 bg-rose-500/10 p-5"><p class="text-sm text-rose-200">Rejected</p><p class="mt-2 text-3xl font-black text-rose-300"><?php echo $summary['rejected']; ?></p></div>
  </div>
  <div class="mt-8 overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900">
    <table class="min-w-full text-left text-sm">
      <thead class="border-b border-slate-800 text-slate-400">
        <tr><th class="px-4 py-4">Player</th><th class="px-4 py-4">Scrim</th><th class="px-4 py-4">Amount</th><th class="px-4 py-4">Transaction</th><th class="px-4 py-4">Proof</th><th class="px-4 py-4">Status</th><th class="px-4 py-4">Action</th></tr>
      </thead>
      <tbody>
      <?php while ($row = $payments->fetch_assoc()): ?>
        <tr class="border-b border-slate-900 <?php echo $row['status'] === 'pending' ? 'bg-amber-500/5' : ''; ?>">
          <td class="px-4 py-4"><?php echo h(player_label($row)); ?></td>
          <td class="px-4 py-4"><?php echo h($row['title']); ?></td>
          <td class="px-4 py-4"><?php echo format_money($row['amount']); ?></td>
          <td class="px-4 py-4"><?php echo h($row['transaction_ref'] ?: '-'); ?></td>
          <td class="px-4 py-4"><?php if ($row['screenshot']): ?><a href="../<?php echo h($row['screenshot']); ?>" target="_blank" class="text-amber-300">View</a><?php endif; ?></td>
          <td class="px-4 py-4">
            <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold <?php echo $row['status'] === 'approved' ? 'bg-emerald-500/20 text-emerald-300' : ($row['status'] === 'rejected' ? 'bg-rose-500/20 text-rose-300' : 'bg-amber-500/20 text-amber-300'); ?>">
              <?php echo strtoupper(h($row['status'])); ?>
            </span>
          </td>
          <td class="px-4 py-4">
            <div class="flex gap-3">
              <a href="approve.php?id=<?php echo (int) $row['id']; ?>" class="text-emerald-300">Approve</a>
              <a href="reject.php?id=<?php echo (int) $row['id']; ?>" class="text-rose-300">Reject</a>
            </div>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include_once __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
