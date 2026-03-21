<?php
include_once __DIR__ . "/../includes/app.php";
require_admin();

$scrimId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editing = $scrimId > 0;
$error = "";
$success = "";
$scrim = [
    'title' => '',
    'match_time' => '',
    'match_date' => '',
    'match_clock' => '',
    'map' => 'Erangel',
    'mode' => 'Squad',
    'entry_fee' => 0,
    'total_slots' => 25,
    'registration_status' => 'open',
    'rules_text' => '',
];
$prizeRows = default_prize_distribution();

if ($editing) {
    $stmt = $conn->prepare("SELECT * FROM scrims WHERE id = ?");
    $stmt->bind_param("i", $scrimId);
    $stmt->execute();
    $scrim = $stmt->get_result()->fetch_assoc() ?: $scrim;
    if (!empty($scrim['match_time'])) {
        $scrim['match_date'] = date('Y-m-d', strtotime((string) $scrim['match_time']));
        $scrim['match_clock'] = date('H:i', strtotime((string) $scrim['match_time']));
    }

    $prizeStmt = $conn->prepare("SELECT rank_position, percentage FROM prizes WHERE scrim_id = ? ORDER BY rank_position ASC");
    $prizeStmt->bind_param("i", $scrimId);
    $prizeStmt->execute();
    $rows = $prizeStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    if ($rows) {
      $prizeRows = [];
      foreach ($rows as $row) {
          $prizeRows[(int) $row['rank_position']] = (float) $row['percentage'];
      }
    }
}

if (isset($_POST['save_scrim'])) {
    $title = trim($_POST['title'] ?? '');
    $matchDate = trim($_POST['match_date'] ?? '');
    $matchClock = trim($_POST['match_clock'] ?? '');
    $map = trim($_POST['map'] ?? '');
    $mode = $_POST['mode'] ?? 'Squad';
    $entryFee = (float) ($_POST['entry_fee'] ?? 0);
    $totalSlots = (int) ($_POST['total_slots'] ?? 0);
    $registrationStatus = $_POST['registration_status'] ?? 'open';
    $rulesText = trim($_POST['rules_text'] ?? '');
    $useDefault = isset($_POST['use_default_distribution']);
    $matchTime = $matchDate !== '' && $matchClock !== '' ? $matchDate . ' ' . $matchClock . ':00' : '';

    if ($title === '' || $matchTime === '' || $totalSlots <= 0) {
        $error = "Title, match time, and total slots are required.";
    } elseif (strtotime($matchTime) === false || strtotime($matchTime) <= time()) {
        $error = "Scrim match date and time must be in the future.";
    } else {
        $prizePool = $entryFee * $totalSlots;
        if ($editing) {
            $stmt = $conn->prepare("UPDATE scrims
                SET title=?, date=DATE(?), time=TIME(?), match_time=?, map=?, mode=?, entry_fee=?, slots=?, total_slots=?, prize_pool=?, registration_status=?, rules_text=?
                WHERE id=?");
            $stmt->bind_param("ssssssdiidssi", $title, $matchTime, $matchTime, $matchTime, $map, $mode, $entryFee, $totalSlots, $totalSlots, $prizePool, $registrationStatus, $rulesText, $scrimId);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO scrims (title, date, time, match_time, map, mode, entry_fee, slots, total_slots, prize_pool, registration_status, rules_text)
                VALUES (?, DATE(?), TIME(?), ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssdiidss", $title, $matchTime, $matchTime, $matchTime, $map, $mode, $entryFee, $totalSlots, $totalSlots, $prizePool, $registrationStatus, $rulesText);
            $stmt->execute();
            $scrimId = $stmt->insert_id;
            $editing = true;
        }

        $distribution = [];
        if ($useDefault) {
            $distribution = default_prize_distribution();
        } else {
            $ranks = $_POST['prize_rank'] ?? [];
            $percentages = $_POST['prize_percentage'] ?? [];
            foreach ($ranks as $index => $rankValue) {
                $rank = (int) $rankValue;
                $percentage = (float) ($percentages[$index] ?? 0);
                if ($rank > 0 && $percentage > 0) {
                    $distribution[$rank] = $percentage;
                }
            }
            if (!$distribution) {
                $distribution = default_prize_distribution();
            }
        }
        save_prize_distribution($conn, $scrimId, $distribution);
        sync_scrim_meta($conn, $scrimId);
        header("Location: create_scrim.php?id=" . $scrimId . "&saved=1");
        exit;
    }
}

if (isset($_GET['saved'])) {
    $success = "Scrim saved successfully.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo $editing ? 'Edit' : 'Create'; ?> Scrim | BGMI</title>
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
    right: 1rem;
    top: 50%;
    width: 0.6rem;
    height: 0.6rem;
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
    padding-right: 2.75rem;
  }
</style>
<div class="mx-auto w-full max-w-5xl flex-1 px-4 py-8 sm:px-6 sm:py-10">
  <div class="rounded-[2rem] border border-slate-800 bg-slate-900 p-5 sm:p-8">
    <h1 class="text-3xl font-black sm:text-4xl"><?php echo $editing ? 'Edit' : 'Create'; ?> Scrim</h1>
    <p class="mt-2 text-slate-400">Prize pool is auto-calculated as entry fee × total slots. You can keep default prize percentages or set your own.</p>
    <?php if ($error !== ""): ?><p class="mt-5 rounded-2xl bg-rose-500/20 px-4 py-3 text-rose-200"><?php echo h($error); ?></p><?php endif; ?>
    <?php if ($success !== ""): ?><p class="mt-5 rounded-2xl bg-emerald-500/20 px-4 py-3 text-emerald-200"><?php echo h($success); ?></p><?php endif; ?>
    <form method="POST" class="mt-8 space-y-6 sm:space-y-8">
      <div class="grid gap-4 md:grid-cols-2">
        <label class="relative block">
          <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-amber-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 19V5"/><path d="M5 12h14"/></svg>
          </span>
          <input type="text" name="title" placeholder="Scrim title" required value="<?php echo h($scrim['title']); ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950 pl-12 pr-4 py-2.5 text-sm sm:py-3 sm:text-base">
        </label>
        <label class="relative block">
          <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-amber-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
          </span>
          <input type="date" name="match_date" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo h($scrim['match_date']); ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950 pl-12 pr-4 py-2.5 text-sm [color-scheme:dark] sm:py-3 sm:text-base">
        </label>
        <label class="relative block">
          <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-amber-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
          </span>
          <input type="time" name="match_clock" required value="<?php echo h($scrim['match_clock']); ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950 pl-12 pr-4 py-2.5 text-sm [color-scheme:dark] sm:py-3 sm:text-base">
        </label>
        <label class="relative block">
          <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-amber-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 17l6-6 4 4 7-7"/><path d="M14 8h6v6"/></svg>
          </span>
          <input type="text" name="map" placeholder="Map" value="<?php echo h($scrim['map']); ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950 pl-12 pr-4 py-2.5 text-sm sm:py-3 sm:text-base">
        </label>
        <label class="mobile-select-wrap block">
          <select name="mode" class="mobile-select rounded-2xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm sm:py-3 sm:text-base">
            <?php foreach (['Solo','Duo','Squad'] as $mode): ?>
              <option value="<?php echo $mode; ?>" <?php echo $scrim['mode'] === $mode ? 'selected' : ''; ?>><?php echo $mode; ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="relative block">
          <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-amber-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14.5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </span>
          <input type="number" step="0.01" min="0" name="entry_fee" placeholder="Entry fee e.g. 99" value="<?php echo h((string) $scrim['entry_fee']); ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950 pl-12 pr-4 py-2.5 text-sm sm:py-3 sm:text-base">
        </label>
        <label class="relative block">
          <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-amber-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19h16"/><path d="M6 16V8"/><path d="M12 16V5"/><path d="M18 16v-4"/></svg>
          </span>
          <input type="number" min="1" name="total_slots" value="<?php echo h((string) $scrim['total_slots']); ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950 pl-12 pr-4 py-2.5 text-sm sm:py-3 sm:text-base">
        </label>
        <label class="mobile-select-wrap block">
          <select name="registration_status" class="mobile-select rounded-2xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm sm:py-3 sm:text-base">
            <?php foreach (['open','closed','full'] as $status): ?>
              <option value="<?php echo $status; ?>" <?php echo $scrim['registration_status'] === $status ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
      <textarea name="rules_text" rows="5" placeholder="Custom rules" class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm sm:text-base"><?php echo h($scrim['rules_text']); ?></textarea>
      <div class="rounded-3xl bg-slate-950 p-4 sm:p-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-xl font-bold sm:text-2xl">Prize Distribution</h2>
            <p class="text-sm text-slate-400">Default system: 1st 50%, 2nd 30%, 3rd 20%</p>
          </div>
          <label class="flex items-center gap-2 text-sm text-slate-300"><input type="checkbox" name="use_default_distribution" value="1"> Use default split</label>
        </div>
        <div class="mt-5 grid gap-4 md:grid-cols-3">
          <?php $index = 0; foreach ($prizeRows as $rank => $percentage): $index++; ?>
            <div class="rounded-2xl border border-slate-800 p-4">
              <input type="number" min="1" name="prize_rank[]" value="<?php echo (int) $rank; ?>" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm">
              <input type="number" step="0.01" min="0" name="prize_percentage[]" value="<?php echo h((string) $percentage); ?>" class="mt-3 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm">
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
        <button type="submit" name="save_scrim" class="rounded-full bg-amber-400 px-6 py-3 text-center font-semibold text-black">Save Scrim</button>
        <?php if ($editing): ?>
          <form action="delete_scrim.php" method="POST" onsubmit="return confirm('Is scrim ko delete karna hai? Iske related bookings, payments aur results bhi remove ho jayenge.');">
            <input type="hidden" name="scrim_id" value="<?php echo (int) $scrimId; ?>">
            <button type="submit" class="rounded-full bg-rose-500/15 px-6 py-3 text-center font-semibold text-rose-300">Delete Scrim</button>
          </form>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>
<?php include_once __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
