<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$scriptPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$isAdminPanel = strpos($scriptPath, '/admin/') !== false;
$prefix = ($isAdminPanel || strpos($scriptPath, '/pages/') !== false || strpos($scriptPath, '/api/') !== false) ? '../' : '';
$currentPage = basename($scriptPath);

if ($isAdminPanel) {
    $links = [
        ['href' => $prefix . 'admin/dashboard.php', 'label' => 'Dashboard'],
        ['href' => $prefix . 'admin/create_scrim.php', 'label' => 'Scrims'],
        ['href' => $prefix . 'admin/payments.php', 'label' => 'Payments'],
        ['href' => $prefix . 'admin/notifications.php', 'label' => 'Broadcasts'],
        ['href' => $prefix . 'pages/scrims.php', 'label' => 'Public View'],
    ];
} else {
    $links = [
        ['href' => $prefix . 'index.php', 'label' => 'Home'],
        ['href' => $prefix . 'pages/scrims.php', 'label' => 'Scrims'],
        ['href' => $prefix . 'pages/leaderboard.php', 'label' => 'Leaderboard'],
    ];
    if (isset($_SESSION['user_id'])) {
        $links[] = ['href' => $prefix . 'pages/my_matches.php', 'label' => 'My Matches'];
        $links[] = ['href' => $prefix . 'pages/history.php', 'label' => 'History'];
        $links[] = ['href' => $prefix . 'pages/notifications.php', 'label' => 'Notifications'];
    }
}

$notificationCount = 0;
if (isset($_SESSION['user_id']) && isset($conn)) {
    $notificationCount = unread_notification_count($conn, (int) $_SESSION['user_id']);
}
?>
<nav class="border-b border-slate-800 bg-slate-950/95 backdrop-blur">
  <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 md:flex-row md:items-center md:justify-between md:px-6">
    <div class="flex items-center justify-between gap-3">
      <a href="<?php echo $prefix . ($isAdminPanel ? 'admin/dashboard.php' : 'index.php'); ?>" class="flex items-center gap-3 text-lg font-bold tracking-wide text-amber-400">
        <img src="<?php echo $prefix; ?>assets/logo-mark.svg" alt="BGMI Scrims logo" class="h-9 w-9 rounded-xl border border-slate-800 bg-slate-900 p-1">
        <span><?php echo $isAdminPanel ? 'BGMI Control Room' : 'BGMI Scrims'; ?></span>
      </a>
      <?php if (($_SESSION['role'] ?? '') === 'admin' && !$isAdminPanel): ?>
        <a href="<?php echo $prefix; ?>admin/dashboard.php" class="rounded-full bg-amber-400 px-3 py-1 text-xs font-semibold text-black">Admin</a>
      <?php endif; ?>
    </div>

    <div class="flex flex-wrap items-center gap-2 text-sm text-slate-100">
      <?php foreach ($links as $link): ?>
        <?php $isActive = $currentPage === basename($link['href']); ?>
        <a href="<?php echo $link['href']; ?>" class="rounded-full px-3 py-2 transition <?php echo $isActive ? 'bg-amber-400 font-semibold text-black' : 'hover:bg-slate-800 hover:text-amber-300'; ?>">
          <?php echo htmlspecialchars($link['label']); ?>
          <?php if ($link['label'] === 'Notifications' && $notificationCount > 0): ?>
            <span class="ml-1 rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-bold text-white"><?php echo $notificationCount; ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>

      <?php if (isset($_SESSION['user_id'])): ?>
        <span class="rounded-full bg-slate-900 px-3 py-2 text-slate-400"><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></span>
        <a href="<?php echo $prefix; ?>pages/logout.php" class="rounded-full bg-rose-500 px-3 py-2 font-semibold text-white hover:bg-rose-400">Logout</a>
      <?php else: ?>
        <a href="<?php echo $prefix; ?>pages/login.php" class="rounded-full px-3 py-2 hover:bg-slate-800 hover:text-amber-300">Login</a>
        <a href="<?php echo $prefix; ?>pages/register.php" class="rounded-full bg-amber-400 px-3 py-2 font-semibold text-black hover:bg-amber-300">Register</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
