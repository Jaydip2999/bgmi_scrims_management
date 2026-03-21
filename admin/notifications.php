<?php
include_once __DIR__ . "/../includes/app.php";
require_admin();

$success = "";
if (isset($_POST['send_broadcast'])) {
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($title !== '' && $message !== '') {
        create_broadcast($conn, $title, $message);
        $success = "Broadcast sent.";
    }
} elseif (isset($_POST['delete_broadcast'])) {
    $notificationId = isset($_POST['notification_id']) ? (int) $_POST['notification_id'] : 0;
    if ($notificationId > 0) {
        $delete = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id IS NULL AND type = 'broadcast'");
        $delete->bind_param("i", $notificationId);
        $delete->execute();
        if ($delete->affected_rows > 0) {
            $success = "Broadcast deleted.";
        }
    }
}

$rows = $conn->query("SELECT * FROM notifications WHERE user_id IS NULL AND type = 'broadcast' ORDER BY created_at DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Broadcasts | BGMI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-screen flex-col bg-slate-950 text-white">
<?php include_once __DIR__ . "/../includes/navbar.php"; ?>
<main class="mx-auto w-full max-w-6xl flex-1 px-6 py-10">
  <h1 class="text-4xl font-black">Broadcast Messages</h1>
  <?php if ($success): ?><p class="mt-4 rounded-2xl bg-emerald-500/20 px-4 py-3 text-emerald-200"><?php echo h($success); ?></p><?php endif; ?>
  <form method="POST" class="mt-8 rounded-[2rem] border border-slate-800 bg-slate-900 p-6">
    <div class="grid gap-4">
      <input type="text" name="title" placeholder="Broadcast title" required class="rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3">
      <textarea name="message" rows="5" placeholder="Write announcement, maintenance notice, room update, payout note, or promo message." required class="rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3"></textarea>
      <button type="submit" name="send_broadcast" class="w-fit rounded-full bg-amber-400 px-6 py-3 font-semibold text-black">Send Broadcast</button>
    </div>
  </form>

  <section class="mt-8 space-y-4">
    <?php while ($row = $rows->fetch_assoc()): ?>
      <article class="rounded-[2rem] border border-slate-800 bg-slate-900 p-6">
        <div class="flex items-center justify-between gap-4">
          <div>
            <h2 class="text-2xl font-bold"><?php echo h($row['title']); ?></h2>
            <span class="mt-1 block text-sm text-slate-500"><?php echo h(date("d M Y, h:i A", strtotime((string) $row['created_at']))); ?></span>
          </div>
          <form method="POST" onsubmit="return confirm('Is broadcast ko delete karna hai?');">
            <input type="hidden" name="notification_id" value="<?php echo (int) $row['id']; ?>">
            <button type="submit" name="delete_broadcast" class="rounded-full bg-rose-500/15 px-4 py-2 text-sm font-semibold text-rose-300">Delete</button>
          </form>
        </div>
        <p class="mt-3 text-slate-300"><?php echo nl2br(h($row['message'])); ?></p>
      </article>
    <?php endwhile; ?>
  </section>
</main>
<?php include_once __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
