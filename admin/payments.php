<?php
include_once __DIR__ . "/../includes/app.php";
require_admin();

$payments = $conn->query("SELECT p.*, u.name, u.team_name, u.email, u.phone, s.title
    FROM payments p
    JOIN users u ON u.id = p.user_id
    JOIN scrims s ON s.id = p.scrim_id
    ORDER BY p.created_at DESC");

$summary = [
    'pending' => (int) $conn->query("SELECT COUNT(*) AS total FROM payments WHERE status='pending'")->fetch_assoc()['total'],
    'approved' => (int) $conn->query("SELECT COUNT(*) AS total FROM payments WHERE status='approved'")->fetch_assoc()['total'],
    'rejected' => (int) $conn->query("SELECT COUNT(*) AS total FROM payments WHERE status='rejected'")->fetch_assoc()['total'],
];

$paymentRows = [];
while ($row = $payments->fetch_assoc()) {
    $paymentRows[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payments | BGMI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-screen flex-col bg-slate-950 text-white">
<?php include_once __DIR__ . "/../includes/navbar.php"; ?>
<div class="mx-auto w-full max-w-7xl flex-1 overflow-x-hidden px-4 py-8 sm:px-6 sm:py-10">
  <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
    <div>
      <h1 class="text-3xl font-black sm:text-4xl">Payment Requests</h1>
      <p class="mt-2 text-slate-400">Status badges are highlighted so pending approvals are immediately visible.</p>
    </div>
  </div>
  <div class="mt-8 grid grid-cols-3 gap-2 sm:gap-4">
    <div class="rounded-2xl border border-amber-500/20 bg-amber-500/10 px-3 py-2.5 sm:px-4 sm:py-3 sm:p-5"><p class="text-[11px] uppercase tracking-[0.14em] text-amber-200 sm:text-sm sm:normal-case sm:tracking-normal">Pending</p><p class="mt-1 text-lg font-black leading-none text-amber-300 sm:mt-2 sm:text-3xl"><?php echo $summary['pending']; ?></p></div>
    <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-3 py-2.5 sm:px-4 sm:py-3 sm:p-5"><p class="text-[11px] uppercase tracking-[0.14em] text-emerald-200 sm:text-sm sm:normal-case sm:tracking-normal">Approved</p><p class="mt-1 text-lg font-black leading-none text-emerald-300 sm:mt-2 sm:text-3xl"><?php echo $summary['approved']; ?></p></div>
    <div class="rounded-2xl border border-rose-500/20 bg-rose-500/10 px-3 py-2.5 sm:px-4 sm:py-3 sm:p-5"><p class="text-[11px] uppercase tracking-[0.14em] text-rose-200 sm:text-sm sm:normal-case sm:tracking-normal">Rejected</p><p class="mt-1 text-lg font-black leading-none text-rose-300 sm:mt-2 sm:text-3xl"><?php echo $summary['rejected']; ?></p></div>
  </div>

  <div class="mt-8 grid gap-4 md:hidden">
    <?php foreach ($paymentRows as $row): ?>
      <?php
        $contactMessage = "Hello " . player_label($row) . ", regarding your payment for " . $row['title'] . ".";
        $whatsAppUrl = whatsapp_link($row['phone'], $contactMessage);
        $mailUrl = mailto_link($row['email'], 'BGMI Scrim Payment Update', 'Hello ' . player_label($row) . ',');
      ?>
      <article class="min-w-0 overflow-hidden rounded-[1.5rem] border border-slate-800 bg-slate-900 p-3.5">
        <div class="grid gap-2">
          <div class="min-w-0">
            <h2 class="truncate text-sm font-bold"><?php echo h(player_label($row)); ?></h2>
            <p class="mt-1 break-words text-[11px] leading-5 text-slate-400"><?php echo h($row['title']); ?></p>
          </div>
          <span class="inline-flex w-fit max-w-full rounded-full px-2.5 py-1 text-[10px] font-bold <?php echo $row['status'] === 'approved' ? 'bg-emerald-500/20 text-emerald-300' : ($row['status'] === 'rejected' ? 'bg-rose-500/20 text-rose-300' : 'bg-amber-500/20 text-amber-300'); ?>">
            <?php echo strtoupper(h($row['status'])); ?>
          </span>
        </div>
        <div class="mt-4 grid gap-2.5 text-[11px] text-slate-300">
          <div class="rounded-xl bg-slate-950 px-3 py-2.5">
            <div class="text-[11px] uppercase tracking-[0.14em] text-slate-500">Amount</div>
            <div class="mt-1 font-semibold text-slate-100"><?php echo format_money($row['amount']); ?></div>
          </div>
          <div class="rounded-xl bg-slate-950 px-3 py-2.5">
            <div class="text-[11px] uppercase tracking-[0.14em] text-slate-500">Transaction</div>
            <div class="mt-1 break-all leading-5 text-slate-100"><?php echo h($row['transaction_ref'] ?: '-'); ?></div>
          </div>
          <div class="rounded-xl bg-slate-950 px-3 py-2.5">
            <div class="text-[11px] uppercase tracking-[0.14em] text-slate-500">Proof</div>
            <div class="mt-1 break-words"><?php if ($row['screenshot']): ?><a href="../<?php echo h($row['screenshot']); ?>" target="_blank" class="text-amber-300">View Screenshot</a><?php else: ?>-<?php endif; ?></div>
          </div>
        </div>
        <div class="mt-4 rounded-2xl border border-slate-800 bg-slate-950/60 p-2.5">
          <div class="mb-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">Contact</div>
          <div class="grid gap-2 text-[11px]">
            <?php if ($whatsAppUrl): ?><a href="<?php echo h($whatsAppUrl); ?>" target="_blank" class="rounded-xl bg-emerald-500/10 px-3 py-2 text-center font-medium text-emerald-300">WhatsApp</a><?php endif; ?>
            <?php if ($mailUrl): ?><a href="<?php echo h($mailUrl); ?>" class="rounded-xl bg-sky-500/10 px-3 py-2 text-center font-medium text-sky-300">Email</a><?php endif; ?>
            <?php if (!$whatsAppUrl && !$mailUrl): ?><span class="rounded-xl bg-slate-900 px-3 py-2 text-center text-slate-500">No contact</span><?php endif; ?>
          </div>
        </div>
        <div class="mt-3 rounded-2xl border border-slate-800 bg-slate-950/60 p-2.5">
          <div class="mb-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">Actions</div>
          <div class="grid gap-2 text-xs">
          <a href="approve.php?id=<?php echo (int) $row['id']; ?>" class="rounded-2xl bg-emerald-500/15 px-4 py-2.5 text-center font-semibold text-emerald-300">Approve</a>
          <a href="reject.php?id=<?php echo (int) $row['id']; ?>" class="rounded-2xl bg-rose-500/15 px-4 py-2.5 text-center font-semibold text-rose-300">Reject</a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <div class="mt-8 hidden overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900 md:block">
    <table class="min-w-full text-left text-sm">
      <thead class="border-b border-slate-800 text-slate-400">
        <tr><th class="px-4 py-4">Player</th><th class="px-4 py-4">Scrim</th><th class="px-4 py-4">Amount</th><th class="px-4 py-4">Transaction</th><th class="px-4 py-4">Proof</th><th class="px-4 py-4">Contact</th><th class="px-4 py-4">Status</th><th class="px-4 py-4">Action</th></tr>
      </thead>
      <tbody>
      <?php foreach ($paymentRows as $row): ?>
        <?php
          $contactMessage = "Hello " . player_label($row) . ", regarding your payment for " . $row['title'] . ".";
          $whatsAppUrl = whatsapp_link($row['phone'], $contactMessage);
          $mailUrl = mailto_link($row['email'], 'BGMI Scrim Payment Update', 'Hello ' . player_label($row) . ',');
        ?>
        <tr class="border-b border-slate-900 <?php echo $row['status'] === 'pending' ? 'bg-amber-500/5' : ''; ?>">
          <td class="px-4 py-4"><?php echo h(player_label($row)); ?></td>
          <td class="px-4 py-4"><?php echo h($row['title']); ?></td>
          <td class="px-4 py-4"><?php echo format_money($row['amount']); ?></td>
          <td class="px-4 py-4"><?php echo h($row['transaction_ref'] ?: '-'); ?></td>
          <td class="px-4 py-4"><?php if ($row['screenshot']): ?><a href="../<?php echo h($row['screenshot']); ?>" target="_blank" class="text-amber-300">View</a><?php endif; ?></td>
          <td class="px-4 py-4">
            <div class="flex flex-wrap gap-3">
              <?php if ($whatsAppUrl): ?><a href="<?php echo h($whatsAppUrl); ?>" target="_blank" class="text-emerald-300">WhatsApp</a><?php endif; ?>
              <?php if ($mailUrl): ?><a href="<?php echo h($mailUrl); ?>" class="text-sky-300">Email</a><?php endif; ?>
              <?php if (!$whatsAppUrl && !$mailUrl): ?><span class="text-slate-500">No contact</span><?php endif; ?>
            </div>
          </td>
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
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include_once __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
