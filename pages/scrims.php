<?php
include_once __DIR__ . "/../includes/app.php";

$statusFilter = $_GET['status'] ?? 'all';
$allowedStatuses = ['all', 'open', 'closed', 'full'];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = 'all';
}
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'upcoming';
$allowedSorts = ['upcoming', 'prize_high', 'slots_low', 'entry_low'];
if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'upcoming';
}
$perPage = 9;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$countSql = "SELECT COUNT(*) AS total FROM scrims s";
if ($statusFilter !== 'all' || $search !== '') {
    $conditions = [];
    if ($statusFilter !== 'all') {
        $conditions[] = "s.registration_status = '" . $conn->real_escape_string($statusFilter) . "'";
    }
    if ($search !== '') {
        $safeSearch = $conn->real_escape_string($search);
        $conditions[] = "(s.title LIKE '%$safeSearch%' OR s.map LIKE '%$safeSearch%' OR s.mode LIKE '%$safeSearch%')";
    }
    $countSql .= " WHERE " . implode(" AND ", $conditions);
}
$totalRows = (int) $conn->query($countSql)->fetch_assoc()['total'];
$totalPages = max(1, (int) ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$sql = "SELECT s.*,
    (SELECT COUNT(*) FROM bookings b WHERE b.scrim_id = s.id AND b.status='approved') AS approved_slots
    FROM scrims s";
if ($statusFilter !== 'all' || $search !== '') {
    $conditions = [];
    if ($statusFilter !== 'all') {
        $conditions[] = "s.registration_status = '" . $conn->real_escape_string($statusFilter) . "'";
    }
    if ($search !== '') {
        $safeSearch = $conn->real_escape_string($search);
        $conditions[] = "(s.title LIKE '%$safeSearch%' OR s.map LIKE '%$safeSearch%' OR s.mode LIKE '%$safeSearch%')";
    }
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
if ($sort === 'prize_high') {
    $sql .= " ORDER BY s.prize_pool DESC, s.match_time ASC";
} elseif ($sort === 'slots_low') {
    $sql .= " ORDER BY (s.total_slots - (SELECT COUNT(*) FROM bookings b WHERE b.scrim_id = s.id AND b.status='approved')) ASC, s.match_time ASC";
} elseif ($sort === 'entry_low') {
    $sql .= " ORDER BY s.entry_fee ASC, s.match_time ASC";
} else {
    $sql .= " ORDER BY
        CASE
          WHEN s.registration_status = 'open' AND s.match_time > DATE_ADD(NOW(), INTERVAL 10 MINUTE) THEN 0
          WHEN s.match_time > NOW() THEN 1
          ELSE 2
        END,
        s.match_time ASC";
}
$sql .= " LIMIT $perPage OFFSET $offset";
$scrims = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Scrims | BGMI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-white">
<?php include_once __DIR__ . "/../includes/navbar.php"; ?>
<section class="mx-auto max-w-7xl px-6 py-10">
  <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
    <div>
      <p class="text-sm uppercase tracking-[0.3em] text-slate-500">Tournament Lobby</p>
      <h1 class="mt-2 text-4xl font-black">Available BGMI scrims</h1>
      <p class="mt-2 text-sm text-slate-400"><?php echo $totalRows; ?> scrims found</p>
    </div>
    <div class="flex flex-wrap gap-2">
      <?php foreach ($allowedStatuses as $filter): ?>
        <a href="?status=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>" class="rounded-2xl px-4 py-2 text-sm <?php echo $filter === $statusFilter ? 'bg-amber-400 font-semibold text-black' : 'bg-slate-900 text-slate-300'; ?>">
          <?php echo ucfirst($filter); ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <form method="GET" class="mt-6 grid gap-3 rounded-2xl border border-slate-800 bg-slate-900 p-4 md:grid-cols-[1fr_220px_140px]">
    <input type="hidden" name="status" value="<?php echo h($statusFilter); ?>">
    <input type="text" name="search" value="<?php echo h($search); ?>" placeholder="Search by title, map, or mode" class="rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3">
    <select name="sort" class="rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3">
      <option value="upcoming" <?php echo $sort === 'upcoming' ? 'selected' : ''; ?>>Upcoming First</option>
      <option value="prize_high" <?php echo $sort === 'prize_high' ? 'selected' : ''; ?>>Highest Prize</option>
      <option value="slots_low" <?php echo $sort === 'slots_low' ? 'selected' : ''; ?>>Lowest Slots Left</option>
      <option value="entry_low" <?php echo $sort === 'entry_low' ? 'selected' : ''; ?>>Lowest Entry</option>
    </select>
    <button type="submit" class="rounded-2xl bg-amber-400 px-4 py-3 font-semibold text-black">Apply</button>
  </form>

  <div class="mt-8 grid gap-6 lg:grid-cols-3">
    <?php while ($scrim = $scrims->fetch_assoc()): ?>
      <?php $available = max(0, (int) $scrim['total_slots'] - (int) $scrim['approved_slots']); ?>
      <?php $isOpen = registration_is_open($scrim + ['available_slots' => $available]); ?>
      <?php $deadline = scrim_registration_deadline($scrim); ?>
      <article class="rounded-2xl border border-slate-800 bg-slate-900 p-6">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-sm text-slate-400"><?php echo h($scrim['mode']); ?> | <?php echo h($scrim['map']); ?></p>
            <h2 class="mt-1 text-2xl font-bold"><?php echo h($scrim['title']); ?></h2>
          </div>
          <span class="rounded-md px-3 py-1 text-xs font-semibold <?php echo $isOpen ? 'bg-emerald-500/20 text-emerald-300' : ($available <= 0 || $scrim['registration_status'] === 'full' ? 'bg-rose-500/20 text-rose-300' : 'bg-slate-800 text-slate-300'); ?>">
            <?php echo $isOpen ? 'OPEN' : ($available <= 0 || $scrim['registration_status'] === 'full' ? 'FULL' : 'CLOSED'); ?>
          </span>
        </div>
        <div class="mt-6 space-y-3 text-sm text-slate-300">
          <div class="flex justify-between"><span>Match Time</span><span data-countdown="<?php echo h((string) $scrim['match_time']); ?>"><?php echo h(date("d M Y, h:i A", strtotime((string) $scrim['match_time']))); ?></span></div>
          <div class="flex justify-between"><span>Entry Fee</span><span class="font-semibold text-amber-300"><?php echo format_money($scrim['entry_fee']); ?></span></div>
          <div class="flex justify-between"><span>Prize Pool</span><span class="font-semibold text-emerald-300"><?php echo format_money($scrim['prize_pool']); ?></span></div>
          <div class="flex justify-between"><span>Available Slots</span><span><?php echo $available; ?>/<?php echo (int) $scrim['total_slots']; ?></span></div>
          <div class="flex justify-between"><span>Registration Closes</span><span><?php echo $deadline ? h(date("d M, h:i A", $deadline)) : '-'; ?></span></div>
        </div>
        <a href="scrim-details.php?id=<?php echo (int) $scrim['id']; ?>" class="mt-6 inline-flex rounded-md bg-amber-400 px-5 py-2 font-semibold text-black">Open Scrim</a>
      </article>
    <?php endwhile; ?>
  </div>
</section>
<?php if ($totalPages > 1): ?>
<div class="mx-auto flex max-w-7xl flex-wrap items-center justify-center gap-3 px-6 pb-8">
  <?php if ($page > 1): ?>
    <a href="?status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $page - 1; ?>" class="rounded-2xl bg-slate-900 px-4 py-2 text-sm text-slate-300">Prev</a>
  <?php endif; ?>
  <?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a href="?status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $i; ?>" class="rounded-2xl px-4 py-2 text-sm <?php echo $i === $page ? 'bg-amber-400 font-semibold text-black' : 'bg-slate-900 text-slate-300'; ?>">
      <?php echo $i; ?>
    </a>
  <?php endfor; ?>
  <?php if ($page < $totalPages): ?>
    <a href="?status=<?php echo urlencode($statusFilter); ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>&page=<?php echo $page + 1; ?>" class="rounded-2xl bg-slate-900 px-4 py-2 text-sm text-slate-300">Next</a>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php include_once __DIR__ . "/../includes/footer.php"; ?>
<script>
document.querySelectorAll("[data-countdown]").forEach((node) => {
  const target = new Date(node.dataset.countdown).getTime();
  const label = node.textContent;
  const tick = () => {
    const diff = target - Date.now();
    if (diff <= 0) {
      node.textContent = "Live / Started";
      return;
    }
    const hours = Math.floor(diff / 3600000);
    const mins = Math.floor((diff % 3600000) / 60000);
    const secs = Math.floor((diff % 60000) / 1000);
    node.textContent = `${label} (${hours}h ${mins}m ${secs}s)`;
  };
  tick();
  setInterval(tick, 1000);
});
</script>
</body>
</html>
