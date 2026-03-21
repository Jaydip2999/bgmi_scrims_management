<?php
include_once __DIR__ . "/../includes/app.php";
require_admin();

$scrimId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$scrim = get_scrim_full($conn, $scrimId);
if (!$scrim) {
    header("Location: dashboard.php");
    exit;
}

$message = "";
if (isset($_POST['save_room'])) {
    $roomId = trim($_POST['room_id'] ?? '');
    $roomPassword = trim($_POST['room_password'] ?? '');
    $stmt = $conn->prepare("UPDATE scrims SET room_id = ?, room_password = ? WHERE id = ?");
    $stmt->bind_param("ssi", $roomId, $roomPassword, $scrimId);
    $stmt->execute();
    $scrim['room_id'] = $roomId;
    $scrim['room_password'] = $roomPassword;
    $message = "Room details updated.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Room Control | BGMI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-screen flex-col bg-slate-950 text-white">
<?php include_once __DIR__ . "/../includes/navbar.php"; ?>
<div class="mx-auto w-full max-w-3xl flex-1 px-6 py-10">
  <div class="rounded-[2rem] border border-slate-800 bg-slate-900 p-8">
    <h1 class="text-4xl font-black">Room Control</h1>
    <p class="mt-2 text-slate-400"><?php echo h($scrim['title']); ?>. Room details become visible to approved players 10 minutes before match time.</p>
    <?php if ($message): ?><p class="mt-5 rounded-2xl bg-emerald-500/20 px-4 py-3 text-emerald-200"><?php echo h($message); ?></p><?php endif; ?>
    <form method="POST" class="mt-8 space-y-4">
      <input type="text" name="room_id" placeholder="Room ID" value="<?php echo h($scrim['room_id']); ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3">
      <input type="text" name="room_password" placeholder="Room Password" value="<?php echo h($scrim['room_password']); ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3">
      <button type="submit" name="save_room" class="rounded-full bg-amber-400 px-6 py-3 font-semibold text-black">Save Room Details</button>
    </form>
  </div>
</div>
<?php include_once __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
