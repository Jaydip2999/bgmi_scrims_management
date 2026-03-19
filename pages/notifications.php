<?php
include_once __DIR__ . "/../includes/app.php";
require_login();

$markRead = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? OR user_id IS NULL");
$markRead->bind_param("i", $_SESSION['user_id']);
$markRead->execute();

$items = fetch_notifications($conn, (int) $_SESSION['user_id'], 50);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Notifications | BGMI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-white">
<?php include_once __DIR__ . "/../includes/navbar.php"; ?>
<main class="mx-auto max-w-5xl px-6 py-10">
  <h1 class="text-4xl font-black">Notifications</h1>
  <div class="mt-8 space-y-4">
    <?php if (!$items): ?>
      <div class="rounded-[2rem] border border-slate-800 bg-slate-900 p-6 text-slate-400">No notifications yet.</div>
    <?php endif; ?>
    <?php foreach ($items as $item): ?>
      <article class="rounded-[2rem] border border-slate-800 bg-slate-900 p-6">
        <div class="flex items-center justify-between gap-4">
          <div>
            <p class="text-xs uppercase tracking-[0.3em] text-slate-500"><?php echo h($item['type']); ?></p>
            <h2 class="mt-2 text-2xl font-bold"><?php echo h($item['title']); ?></h2>
          </div>
          <span class="text-sm text-slate-500"><?php echo h(date("d M Y, h:i A", strtotime((string) $item['created_at']))); ?></span>
        </div>
        <p class="mt-3 text-slate-300"><?php echo nl2br(h($item['message'])); ?></p>
      </article>
    <?php endforeach; ?>
  </div>
</main>
<?php include_once __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
