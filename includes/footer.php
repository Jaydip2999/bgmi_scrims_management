<?php
$footerPrefix = (strpos(str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? ''), '/admin/') !== false || strpos(str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? ''), '/pages/') !== false || strpos(str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? ''), '/api/') !== false) ? '../' : '';
?>
<footer class="mt-auto border-t border-slate-800 bg-black/20">
  <div class="mx-auto flex max-w-7xl flex-col gap-4 px-6 py-10 text-sm text-slate-400 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-3">
      <img src="<?php echo $footerPrefix; ?>assets/logo-mark.svg" alt="BGMI Scrims" class="h-9 w-9 rounded-xl border border-slate-800 bg-slate-900 p-1">
      <span>BGMI Scrims platform for upcoming matches, quick joins, live rooms, and leaderboards.</span>
    </div>
    <div class="flex flex-wrap gap-4">
      <a href="<?php echo $footerPrefix; ?>pages/scrims.php" class="hover:text-amber-300">Scrims</a>
      <a href="<?php echo $footerPrefix; ?>pages/leaderboard.php" class="hover:text-amber-300">Leaderboard</a>
      <a href="<?php echo $footerPrefix; ?>pages/register.php" class="hover:text-amber-300">Register</a>
    </div>
  </div>
</footer>
