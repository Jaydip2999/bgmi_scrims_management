<?php
include_once __DIR__ . "/includes/app.php";

$biggest = $conn->query("SELECT s.*,
    (SELECT COUNT(*) FROM bookings b WHERE b.scrim_id = s.id AND b.status='approved') AS approved_slots
    FROM scrims s
    WHERE s.match_time > NOW()
    ORDER BY s.prize_pool DESC, s.match_time ASC
    LIMIT 3");

$fewSlots = $conn->query("SELECT s.*,
    (SELECT COUNT(*) FROM bookings b WHERE b.scrim_id = s.id AND b.status='approved') AS approved_slots
    FROM scrims s
    WHERE s.match_time > NOW()
    ORDER BY (s.total_slots - (SELECT COUNT(*) FROM bookings b WHERE b.scrim_id = s.id AND b.status='approved')) ASC, s.match_time ASC
    LIMIT 3");

$closingSoon = $conn->query("SELECT s.*,
    (SELECT COUNT(*) FROM bookings b WHERE b.scrim_id = s.id AND b.status='approved') AS approved_slots
    FROM scrims s
    WHERE s.match_time > NOW() AND s.match_time <= DATE_ADD(NOW(), INTERVAL 25 MINUTE)
    ORDER BY s.match_time ASC
    LIMIT 3");

function landing_card_clean(array $scrim): void
{
    $available = max(0, (int) $scrim['total_slots'] - (int) $scrim['approved_slots']);
    $deadline = scrim_registration_deadline($scrim);
    ?>
    <article class="rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-[0_12px_40px_rgba(0,0,0,0.18)]">
      <div class="flex items-start justify-between gap-4">
        <div>
          <p class="text-sm text-slate-400"><?php echo h($scrim['mode']); ?> | <?php echo h($scrim['map']); ?></p>
          <h3 class="mt-1 text-xl font-bold"><?php echo h($scrim['title']); ?></h3>
        </div>
        <span class="rounded-full bg-slate-950 px-3 py-1 text-xs text-slate-300"><?php echo h(date("d M, h:i A", strtotime((string) $scrim['match_time']))); ?></span>
      </div>
      <div class="mt-5 space-y-2 text-sm text-slate-300">
        <div class="flex justify-between"><span>Prize Pool</span><span class="font-semibold text-amber-300"><?php echo format_money($scrim['prize_pool']); ?></span></div>
        <div class="flex justify-between"><span>Slots Left</span><span class="font-semibold text-emerald-300"><?php echo $available; ?></span></div>
        <div class="flex justify-between"><span>Close Time</span><span><?php echo $deadline ? h(date("d M, h:i A", $deadline)) : '-'; ?></span></div>
      </div>
      <a href="pages/scrim-details.php?id=<?php echo (int) $scrim['id']; ?>" class="mt-5 inline-flex rounded-full bg-amber-400 px-5 py-2 font-semibold text-black">View Scrim</a>
    </article>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>BGMI Scrims | Upcoming Scrims & Leaderboards</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Join upcoming BGMI scrims, check live prize pools, watch slots fill fast, and track leaderboards on one clean tournament platform.">
  <meta name="keywords" content="BGMI scrims, BGMI tournaments, esports scrims, BGMI leaderboard, BGMI rooms, custom room scrims">
  <meta name="robots" content="index,follow,max-image-preview:large">
  <link rel="canonical" href="http://localhost/php/bgmi_scrims_management/">
  <meta property="og:title" content="BGMI Scrims | Upcoming Scrims & Leaderboards">
  <meta property="og:description" content="Upcoming biggest scrims, fast-filling lobbies, and clean leaderboard tracking for BGMI players.">
  <meta property="og:type" content="website">
  <meta property="og:url" content="http://localhost/php/bgmi_scrims_management/">
  <meta property="og:image" content="http://localhost/php/bgmi_scrims_management/assets/gaming-hero.svg">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="theme-color" content="#020617">
  <link rel="icon" href="favicon.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="favicon.svg">
  <link rel="manifest" href="site.webmanifest">
  <script type="application/ld+json">
  {
    "@context":"https://schema.org",
    "@type":"WebSite",
    "name":"BGMI Scrims",
    "url":"http://localhost/php/bgmi_scrims_management/",
    "potentialAction":{
      "@type":"SearchAction",
      "target":"http://localhost/php/bgmi_scrims_management/pages/scrims.php?status={search_term_string}",
      "query-input":"required name=search_term_string"
    }
  }
  </script>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-white">
<?php include_once __DIR__ . "/includes/navbar.php"; ?>

<section class="relative overflow-hidden border-b border-slate-800 bg-[radial-gradient(circle_at_top,_rgba(245,158,11,0.18),_transparent_32%),linear-gradient(135deg,#020617,#0f172a_58%,#111827)]">
  <div class="mx-auto grid max-w-7xl gap-10 px-6 py-14 lg:grid-cols-[1.05fr_.95fr] lg:items-center">
    <div>
      <span class="inline-flex rounded-full border border-amber-400/20 bg-amber-400/10 px-4 py-1 text-sm text-amber-300">Upcoming BGMI scrims</span>
      <h1 class="mt-5 max-w-3xl text-4xl font-black leading-tight md:text-5xl">Find the next big lobby, grab a slot, and track results without the clutter.</h1>
      <p class="mt-4 max-w-2xl text-base text-slate-300">Clean scrim discovery, prize pool visibility, fast registration flow, and leaderboard tracking.</p>
      <div class="mt-7 flex flex-wrap gap-3">
        <a href="pages/scrims.php" class="rounded-full bg-amber-400 px-6 py-3 font-semibold text-black hover:bg-amber-300">Join Scrims</a>
        <a href="pages/leaderboard.php" class="rounded-full border border-slate-700 px-6 py-3 font-semibold hover:border-amber-400 hover:text-amber-300">Leaderboard</a>
      </div>
    </div>
    <div class="space-y-5">
      <img src="assets/gaming-hero.svg" alt="BGMI scrim platform preview" class="w-full rounded-2xl border border-slate-800 bg-slate-900 p-3 shadow-2xl">
    </div>
  </div>
</section>

<section class="mx-auto max-w-7xl px-6 py-12">
  <div class="mb-6">
    <p class="text-sm uppercase tracking-[0.3em] text-slate-500">Featured</p>
    <h2 class="mt-2 text-3xl font-bold">Upcoming biggest scrims</h2>
  </div>
  <div class="grid gap-6 lg:grid-cols-3">
    <?php while ($scrim = $biggest->fetch_assoc()): landing_card_clean($scrim); endwhile; ?>
  </div>
</section>

<section class="mx-auto max-w-7xl px-6 py-4">
  <div class="grid gap-6 lg:grid-cols-2">
    <div>
      <div class="mb-6">
        <p class="text-sm uppercase tracking-[0.3em] text-slate-500">Filling Fast</p>
        <h2 class="mt-2 text-3xl font-bold">Lowest slots remaining</h2>
      </div>
      <div class="grid gap-4">
        <?php while ($scrim = $fewSlots->fetch_assoc()): landing_card_clean($scrim); endwhile; ?>
      </div>
    </div>
    <div>
      <div class="mb-6">
        <p class="text-sm uppercase tracking-[0.3em] text-slate-500">Closing Soon</p>
        <h2 class="mt-2 text-3xl font-bold">Registration closing in 15 minutes</h2>
      </div>
      <div class="grid gap-4">
        <?php
        $hasClosingSoon = false;
        while ($scrim = $closingSoon->fetch_assoc()):
          $deadline = scrim_registration_deadline($scrim);
          if ($deadline === null || $deadline < time() || $deadline > time() + 900) {
              continue;
          }
          $hasClosingSoon = true;
          landing_card_clean($scrim);
        endwhile;
        if (!$hasClosingSoon):
        ?>
        <div class="rounded-2xl border border-dashed border-slate-700 bg-slate-900 p-8 text-slate-400">No scrims are in the final 15-minute registration window right now.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php include_once __DIR__ . "/includes/footer.php"; ?>
</body>
</html>
