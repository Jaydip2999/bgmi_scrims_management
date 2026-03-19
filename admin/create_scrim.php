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
    $matchTime = trim($_POST['match_time'] ?? '');
    $map = trim($_POST['map'] ?? '');
    $mode = $_POST['mode'] ?? 'Squad';
    $entryFee = (float) ($_POST['entry_fee'] ?? 0);
    $totalSlots = (int) ($_POST['total_slots'] ?? 0);
    $registrationStatus = $_POST['registration_status'] ?? 'open';
    $rulesText = trim($_POST['rules_text'] ?? '');
    $useDefault = isset($_POST['use_default_distribution']);

    if ($title === '' || $matchTime === '' || $totalSlots <= 0) {
        $error = "Title, match time, and total slots are required.";
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
<body class="min-h-screen bg-slate-950 text-white">
<?php include_once __DIR__ . "/../includes/navbar.php"; ?>
<div class="mx-auto max-w-5xl px-6 py-10">
  <div class="rounded-[2rem] border border-slate-800 bg-slate-900 p-8">
    <h1 class="text-4xl font-black"><?php echo $editing ? 'Edit Scrim' : 'Create Scrim'; ?></h1>
    <p class="mt-2 text-slate-400">Prize pool is auto-calculated as entry fee × total slots. You can keep default prize percentages or set your own.</p>
    <?php if ($error !== ""): ?><p class="mt-5 rounded-2xl bg-rose-500/20 px-4 py-3 text-rose-200"><?php echo h($error); ?></p><?php endif; ?>
    <?php if ($success !== ""): ?><p class="mt-5 rounded-2xl bg-emerald-500/20 px-4 py-3 text-emerald-200"><?php echo h($success); ?></p><?php endif; ?>
    <form method="POST" class="mt-8 space-y-8">
      <div class="grid gap-4 md:grid-cols-2">
        <input type="text" name="title" placeholder="Scrim title" required value="<?php echo h($scrim['title']); ?>" class="rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3">
        <input type="datetime-local" name="match_time" required value="<?php echo h($scrim['match_time'] ? date('Y-m-d\TH:i', strtotime((string) $scrim['match_time'])) : ''); ?>" class="rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3">
        <input type="text" name="map" placeholder="Map" value="<?php echo h($scrim['map']); ?>" class="rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3">
        <select name="mode" class="rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3">
          <?php foreach (['Solo','Duo','Squad'] as $mode): ?>
            <option value="<?php echo $mode; ?>" <?php echo $scrim['mode'] === $mode ? 'selected' : ''; ?>><?php echo $mode; ?></option>
          <?php endforeach; ?>
        </select>
        <input type="number" step="0.01" min="0" name="entry_fee" value="<?php echo h((string) $scrim['entry_fee']); ?>" class="rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3">
        <input type="number" min="1" name="total_slots" value="<?php echo h((string) $scrim['total_slots']); ?>" class="rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3">
        <select name="registration_status" class="rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3">
          <?php foreach (['open','closed','full'] as $status): ?>
            <option value="<?php echo $status; ?>" <?php echo $scrim['registration_status'] === $status ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <textarea name="rules_text" rows="5" placeholder="Custom rules" class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3"><?php echo h($scrim['rules_text']); ?></textarea>
      <div class="rounded-3xl bg-slate-950 p-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-2xl font-bold">Prize Distribution</h2>
            <p class="text-sm text-slate-400">Default system: 1st 50%, 2nd 30%, 3rd 20%</p>
          </div>
          <label class="flex items-center gap-2 text-sm text-slate-300"><input type="checkbox" name="use_default_distribution" value="1"> Use default split</label>
        </div>
        <div class="mt-5 grid gap-4 md:grid-cols-3">
          <?php $index = 0; foreach ($prizeRows as $rank => $percentage): $index++; ?>
            <div class="rounded-2xl border border-slate-800 p-4">
              <input type="number" min="1" name="prize_rank[]" value="<?php echo (int) $rank; ?>" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2">
              <input type="number" step="0.01" min="0" name="prize_percentage[]" value="<?php echo h((string) $percentage); ?>" class="mt-3 w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2">
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <button type="submit" name="save_scrim" class="rounded-full bg-amber-400 px-6 py-3 font-semibold text-black">Save Scrim</button>
    </form>
  </div>
</div>
<?php include_once __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
