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
<nav class="sticky top-0 z-40 border-b border-slate-800 bg-slate-950/95 backdrop-blur">
  <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6">
    <div class="flex items-center justify-between gap-3">
      <a href="<?php echo $prefix . ($isAdminPanel ? 'admin/dashboard.php' : 'index.php'); ?>" class="flex min-w-0 items-center gap-3 text-base font-bold tracking-wide text-amber-400 sm:text-lg">
        <img src="<?php echo $prefix; ?>assets/logo-mark.svg" alt="BGMI Scrims logo" class="h-9 w-9 rounded-xl border border-slate-800 bg-slate-900 p-1">
        <span class="truncate"><?php echo $isAdminPanel ? 'BGMI Control Room' : 'BGMI Scrims'; ?></span>
      </a>
      <div class="hidden items-center gap-2 text-sm text-slate-100 md:flex">
        <?php if (($_SESSION['role'] ?? '') === 'admin' && !$isAdminPanel): ?>
          <a href="<?php echo $prefix; ?>admin/dashboard.php" class="rounded-full bg-amber-400 px-3 py-1 text-xs font-semibold text-black">Admin</a>
        <?php endif; ?>

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
          <span class="max-w-[150px] truncate rounded-full bg-slate-900 px-3 py-2 text-slate-400"><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></span>
          <a href="<?php echo $prefix; ?>pages/logout.php" class="rounded-full bg-rose-500 px-3 py-2 font-semibold text-white hover:bg-rose-400">Logout</a>
        <?php else: ?>
          <a href="<?php echo $prefix; ?>pages/login.php" class="rounded-full px-3 py-2 hover:bg-slate-800 hover:text-amber-300">Login</a>
          <a href="<?php echo $prefix; ?>pages/register.php" class="rounded-full bg-amber-400 px-3 py-2 font-semibold text-black hover:bg-amber-300">Register</a>
        <?php endif; ?>
      </div>

      <button type="button" id="mobile-nav-toggle" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-800 bg-slate-900 text-slate-100 md:hidden" aria-expanded="false" aria-controls="mobile-nav-panel" aria-label="Toggle navigation">
        <svg id="mobile-nav-open" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/></svg>
        <svg id="mobile-nav-close" xmlns="http://www.w3.org/2000/svg" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12"/><path d="m18 6-12 12"/></svg>
      </button>
    </div>

    <div id="mobile-nav-panel" class="hidden pt-4 md:hidden">
      <div class="rounded-[1.5rem] border border-slate-800 bg-slate-900 p-3 shadow-xl">
        <?php if (isset($_SESSION['user_id'])): ?>
          <div class="mb-3 rounded-2xl bg-slate-950 px-4 py-3 text-sm text-slate-300">
            Signed in as <span class="font-semibold text-white"><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></span>
          </div>
        <?php endif; ?>

        <?php if (($_SESSION['role'] ?? '') === 'admin' && !$isAdminPanel): ?>
          <a href="<?php echo $prefix; ?>admin/dashboard.php" class="mb-2 block rounded-2xl bg-amber-400 px-4 py-3 text-sm font-semibold text-black">Open Admin Panel</a>
        <?php endif; ?>

        <div class="grid gap-2">
          <?php foreach ($links as $link): ?>
            <?php $isActive = $currentPage === basename($link['href']); ?>
            <a href="<?php echo $link['href']; ?>" class="flex items-center justify-between rounded-2xl px-4 py-3 text-sm transition <?php echo $isActive ? 'bg-amber-400 font-semibold text-black' : 'bg-slate-950 text-slate-100'; ?>">
              <span><?php echo htmlspecialchars($link['label']); ?></span>
              <?php if ($link['label'] === 'Notifications' && $notificationCount > 0): ?>
                <span class="rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-bold text-white"><?php echo $notificationCount; ?></span>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>

        <div class="mt-3 grid gap-2">
          <?php if (isset($_SESSION['user_id'])): ?>
            <a href="<?php echo $prefix; ?>pages/logout.php" class="rounded-2xl bg-rose-500 px-4 py-3 text-center text-sm font-semibold text-white">Logout</a>
          <?php else: ?>
            <a href="<?php echo $prefix; ?>pages/login.php" class="rounded-2xl bg-slate-950 px-4 py-3 text-center text-sm font-semibold text-slate-100">Login</a>
            <a href="<?php echo $prefix; ?>pages/register.php" class="rounded-2xl bg-amber-400 px-4 py-3 text-center text-sm font-semibold text-black">Register</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</nav>
<script>
(() => {
  const toggle = document.getElementById('mobile-nav-toggle');
  const panel = document.getElementById('mobile-nav-panel');
  const openIcon = document.getElementById('mobile-nav-open');
  const closeIcon = document.getElementById('mobile-nav-close');
  if (!toggle || !panel) return;

  toggle.addEventListener('click', () => {
    const isOpen = !panel.classList.contains('hidden');
    panel.classList.toggle('hidden', isOpen);
    openIcon?.classList.toggle('hidden', !isOpen);
    closeIcon?.classList.toggle('hidden', isOpen);
    toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
  });
})();
</script>
