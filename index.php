<?php
include_once __DIR__ . "/includes/app.php";

$biggest = $conn->query("SELECT s.*,
    (SELECT COUNT(*) FROM bookings b WHERE b.scrim_id = s.id AND b.status='approved') AS approved_slots
    FROM scrims s
    WHERE s.match_time > NOW()
    ORDER BY s.prize_pool DESC, s.match_time ASC
    LIMIT 3");

$closingSoon = $conn->query("SELECT s.*,
    (SELECT COUNT(*) FROM bookings b WHERE b.scrim_id = s.id AND b.status='approved') AS approved_slots
    FROM scrims s
    WHERE s.match_time > NOW() AND s.match_time <= DATE_ADD(NOW(), INTERVAL 25 MINUTE)
    ORDER BY s.match_time ASC
    LIMIT 3");

$latestResults = $conn->query("SELECT s.title, r.rank_position, r.points, u.name, u.team_name
    FROM results r
    JOIN scrims s ON s.id = r.scrim_id
    JOIN users u ON u.id = r.user_id
    ORDER BY r.created_at DESC
    LIMIT 3");

function landing_card_clean(array $scrim): void
{
    $available = max(0, (int) $scrim['total_slots'] - (int) $scrim['approved_slots']);
    $deadline = scrim_registration_deadline($scrim);
    $isOpen = registration_is_open($scrim + ['available_slots' => $available]);
    ?>
    <article class="rounded-[1.5rem] border border-slate-800 bg-slate-900 p-5 shadow-[0_14px_36px_rgba(0,0,0,0.18)] sm:p-6">
      <div class="flex items-start justify-between gap-4">
        <div>
          <p class="text-sm text-slate-400"><?php echo h($scrim['mode']); ?> | <?php echo h($scrim['map']); ?></p>
          <h3 class="mt-1 text-lg font-bold text-white sm:text-xl"><?php echo h($scrim['title']); ?></h3>
        </div>
        <span class="rounded-full px-3 py-1 text-xs font-semibold <?php echo $isOpen ? 'bg-emerald-500/15 text-emerald-300' : 'bg-slate-950 text-slate-300'; ?>">
          <?php echo $isOpen ? 'OPEN' : 'LIMITED'; ?>
        </span>
      </div>
      <div class="mt-5 grid gap-3 text-sm text-slate-300 sm:grid-cols-2">
        <div class="rounded-2xl bg-slate-950 px-4 py-3">
          <div class="text-xs uppercase tracking-[0.22em] text-slate-500">Match Time</div>
          <div class="mt-1 font-semibold"><?php echo h(date("d M Y, h:i A", strtotime((string) $scrim['match_time']))); ?></div>
        </div>
        <div class="rounded-2xl bg-slate-950 px-4 py-3">
          <div class="text-xs uppercase tracking-[0.22em] text-slate-500">Prize Pool</div>
          <div class="mt-1 font-semibold text-amber-300"><?php echo format_money($scrim['prize_pool']); ?></div>
        </div>
        <div class="rounded-2xl bg-slate-950 px-4 py-3">
          <div class="text-xs uppercase tracking-[0.22em] text-slate-500">Slots Left</div>
          <div class="mt-1 font-semibold text-emerald-300"><?php echo $available; ?>/<?php echo (int) $scrim['total_slots']; ?></div>
        </div>
        <div class="rounded-2xl bg-slate-950 px-4 py-3">
          <div class="text-xs uppercase tracking-[0.22em] text-slate-500">Registration Closes</div>
          <div class="mt-1 font-semibold"><?php echo $deadline ? h(date("d M, h:i A", $deadline)) : '-'; ?></div>
        </div>
      </div>
      <div class="mt-5 flex flex-col gap-3 sm:flex-row">
        <a href="pages/scrim-details.php?id=<?php echo (int) $scrim['id']; ?>" class="rounded-full bg-amber-400 px-5 py-3 text-center font-semibold text-black">View Details</a>
        <a href="pages/payment.php?scrim_id=<?php echo (int) $scrim['id']; ?>" class="rounded-full border border-slate-700 px-5 py-3 text-center font-semibold text-slate-200 hover:border-amber-400 hover:text-amber-300">Join Now</a>
      </div>
    </article>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>BGMI Scrims | Tournament Lobbies, Slots, Payments and Results</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Professional BGMI scrim platform with upcoming matches, clean registration flow, payment proof tracking, room details, and result history for players and teams.">
  <meta name="keywords" content="BGMI scrims, BGMI tournaments, esports scrims, BGMI leaderboard, BGMI rooms, custom room scrims, BGMI paid scrims">
  <meta name="robots" content="index,follow,max-image-preview:large">
  <link rel="canonical" href="http://localhost/php/bgmi_scrims_management/">
  <meta property="og:title" content="BGMI Scrims | Tournament Lobbies, Slots, Payments and Results">
  <meta property="og:description" content="Join trusted BGMI scrims, track registrations, upload payment proof, and check live result updates in one place.">
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
<body class="flex min-h-screen flex-col bg-slate-950 text-white">
<?php include_once __DIR__ . "/includes/navbar.php"; ?>

<section class="relative overflow-hidden border-b border-slate-800 bg-[radial-gradient(circle_at_top_left,_rgba(245,158,11,0.14),_transparent_28%),linear-gradient(180deg,#020617,#0f172a_60%,#111827)]">
  <div class="absolute inset-0 opacity-10" style="background-image:url('assets/gaming-grid.svg');background-size:580px;"></div>
  <div class="relative mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 sm:py-14 lg:grid-cols-[1.05fr_.95fr] lg:items-center lg:py-18">
    <div>
      <span class="inline-flex rounded-full border border-amber-400/20 bg-amber-400/10 px-4 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-amber-300 sm:text-sm">BGMI Scrims Platform</span>
      <h1 class="mt-4 max-w-4xl text-3xl font-black leading-tight sm:text-5xl lg:text-6xl">Find trusted scrims, book slots faster, and stay ready for match time.</h1>
      <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-300 sm:text-base">Upcoming matches, slot visibility, payment proof tracking, room access, and result history are all structured in one clean tournament flow that works well on mobile.</p>
      <div class="mt-6 flex flex-col gap-3 sm:flex-row">
        <a href="pages/scrims.php" class="rounded-full bg-amber-400 px-6 py-3 text-center font-semibold text-black hover:bg-amber-300">Browse Live Scrims</a>
        <a href="pages/register.php" class="rounded-full border border-slate-700 px-6 py-3 text-center font-semibold hover:border-amber-400 hover:text-amber-300">Create Player Account</a>
      </div>
    </div>

    <div class="grid gap-4">
      <div class="rounded-[1.75rem] border border-slate-800 bg-slate-900/90 p-3 shadow-2xl">
        <img src="assets/gaming-hero.svg" alt="BGMI scrim platform preview" class="w-full rounded-[1.3rem] border border-slate-800 bg-slate-950 p-2">
      </div>
      <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-[1.25rem] border border-slate-800 bg-slate-900/85 p-4">
          <p class="text-xs uppercase tracking-[0.22em] text-slate-500">Proof</p>
          <p class="mt-2 text-sm font-bold sm:text-base">Payment review</p>
          <p class="mt-2 text-xs leading-6 text-slate-400 sm:text-sm">Proof upload and approval flow stays visible to players.</p>
        </div>
        <div class="rounded-[1.25rem] border border-slate-800 bg-slate-900/85 p-4">
          <p class="text-xs uppercase tracking-[0.22em] text-slate-500">Access</p>
          <p class="mt-2 text-sm font-bold sm:text-base">Room release timing</p>
          <p class="mt-2 text-xs leading-6 text-slate-400 sm:text-sm">Room details open near match start, not randomly in chat.</p>
        </div>
        <div class="rounded-[1.25rem] border border-slate-800 bg-slate-900/85 p-4">
          <p class="text-xs uppercase tracking-[0.22em] text-slate-500">Safety</p>
          <p class="mt-2 text-sm font-bold sm:text-base">Duplicate checks</p>
          <p class="mt-2 text-xs leading-6 text-slate-400 sm:text-sm">BGMI UID and phone checks help reduce fake registrations.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<main class="mx-auto w-full max-w-7xl flex-1 px-4 py-10 sm:px-6 sm:py-12">
  <section class="mt-10 sm:mt-12">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <p class="text-sm uppercase tracking-[0.3em] text-slate-500">Featured Scrims</p>
        <h2 class="mt-2 text-2xl font-bold sm:text-3xl">High-value upcoming lobbies</h2>
      </div>
      <a href="pages/scrims.php" class="text-sm font-semibold text-amber-300">View all scrims</a>
    </div>
    <div class="grid gap-6 lg:grid-cols-3">
      <?php while ($scrim = $biggest->fetch_assoc()): landing_card_clean($scrim); endwhile; ?>
    </div>
  </section>

  <section class="mt-10 grid gap-6 lg:grid-cols-[1.05fr_.95fr] sm:mt-12">
    <div>
      <div>
        <div class="mb-6">
          <p class="text-sm uppercase tracking-[0.3em] text-slate-500">Closing Soon</p>
          <h2 class="mt-2 text-2xl font-bold sm:text-3xl">Final registration window</h2>
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
          <div class="rounded-[1.5rem] border border-dashed border-slate-700 bg-slate-900 p-6 text-sm text-slate-400 sm:p-8">No scrims are inside the last 15-minute registration window right now.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="rounded-[1.75rem] border border-slate-800 bg-slate-900 p-5 sm:p-7">
        <p class="text-sm uppercase tracking-[0.3em] text-slate-500">Recent Result Feed</p>
        <h2 class="mt-2 text-2xl font-bold sm:text-3xl">Latest recorded performances</h2>
        <div class="mt-6 space-y-3 sm:space-y-4">
          <?php if ($latestResults->num_rows === 0): ?>
            <div class="rounded-[1.4rem] bg-slate-950 p-5 text-sm text-slate-400">Latest winners and point updates will appear here after admins upload match results.</div>
          <?php else: ?>
            <?php while ($row = $latestResults->fetch_assoc()): ?>
              <div class="rounded-[1.4rem] bg-slate-950 p-4 sm:p-5">
                <div class="flex items-start justify-between gap-4">
                  <div>
                    <p class="font-semibold text-white"><?php echo h($row['title']); ?></p>
                    <p class="mt-1 text-sm text-slate-400"><?php echo h(player_label($row)); ?></p>
                  </div>
                  <span class="rounded-full bg-amber-400/10 px-3 py-1 text-xs font-semibold text-amber-300">#<?php echo (int) $row['rank_position']; ?></span>
                </div>
                <div class="mt-3 inline-flex rounded-full bg-emerald-500/10 px-3 py-1 text-sm font-semibold text-emerald-300"><?php echo (int) $row['points']; ?> pts</div>
              </div>
            <?php endwhile; ?>
          <?php endif; ?>
        </div>
    </div>
  </section>

  <section class="mt-10 grid gap-6 lg:grid-cols-[1.05fr_.95fr] sm:mt-12">
    <div class="rounded-[1.75rem] border border-slate-800 bg-slate-900 p-5 sm:p-7">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p class="text-sm uppercase tracking-[0.3em] text-slate-500">Why Players Trust It</p>
          <h2 class="mt-2 text-2xl font-bold sm:text-3xl">Built like a real scrim control panel</h2>
        </div>
        <a href="pages/notifications.php" class="text-sm font-semibold text-amber-300">Check notifications</a>
      </div>
      <div class="mt-6 grid gap-4 md:grid-cols-2">
        <div class="rounded-[1.4rem] bg-slate-950 p-4 sm:p-5">
          <h3 class="text-base font-bold sm:text-lg">Transparent slots and timings</h3>
          <p class="mt-2 text-sm leading-6 text-slate-400">Players can see match time, registration deadline, slot pressure, and room release timing without chasing admins manually.</p>
        </div>
        <div class="rounded-[1.4rem] bg-slate-950 p-4 sm:p-5">
          <h3 class="text-base font-bold sm:text-lg">Professional approval flow</h3>
          <p class="mt-2 text-sm leading-6 text-slate-400">Payments, player approvals, room details, result uploads, and payout tracking are all visible through structured pages.</p>
        </div>
        <div class="rounded-[1.4rem] bg-slate-950 p-4 sm:p-5">
          <h3 class="text-base font-bold sm:text-lg">Real match history</h3>
          <p class="mt-2 text-sm leading-6 text-slate-400">Every player can later check kills, ranking, points, and payout status instead of relying only on group chat messages.</p>
        </div>
        <div class="rounded-[1.4rem] bg-slate-950 p-4 sm:p-5">
          <h3 class="text-base font-bold sm:text-lg">Fewer fake duplicate entries</h3>
          <p class="mt-2 text-sm leading-6 text-slate-400">Phone number and BGMI UID safeguards help maintain cleaner registration quality across scrims.</p>
        </div>
      </div>
    </div>

    <div class="rounded-[1.75rem] border border-slate-800 bg-slate-900 p-5 sm:p-7">
      <p class="text-sm uppercase tracking-[0.3em] text-slate-500">How It Works</p>
      <h2 class="mt-2 text-2xl font-bold sm:text-3xl">Simple match-day flow</h2>
      <div class="mt-6 space-y-3 sm:space-y-4">
        <div class="rounded-[1.4rem] bg-slate-950 p-4 sm:p-5">
          <div class="text-xs uppercase tracking-[0.22em] text-amber-300">Step 1</div>
          <p class="mt-2 font-semibold">Pick an upcoming scrim</p>
          <p class="mt-2 text-sm text-slate-400">Browse prize pool, slots left, mode, map, and timing before joining.</p>
        </div>
        <div class="rounded-[1.4rem] bg-slate-950 p-4 sm:p-5">
          <div class="text-xs uppercase tracking-[0.22em] text-amber-300">Step 2</div>
          <p class="mt-2 font-semibold">Upload payment proof</p>
          <p class="mt-2 text-sm text-slate-400">Submit UTR and screenshot so admin can verify and approve your slot.</p>
        </div>
        <div class="rounded-[1.4rem] bg-slate-950 p-4 sm:p-5">
          <div class="text-xs uppercase tracking-[0.22em] text-amber-300">Step 3</div>
          <p class="mt-2 font-semibold">Get room details and results</p>
          <p class="mt-2 text-sm text-slate-400">Approved players get room access at the right time and later see proper leaderboard updates.</p>
        </div>
      </div>
    </div>
  </section>
</main>

<?php include_once __DIR__ . "/includes/footer.php"; ?>
</body>
</html>
