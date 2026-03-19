<?php
include_once __DIR__ . "/../includes/app.php";
require_login();

$scrimId = isset($_GET['scrim_id']) ? (int) $_GET['scrim_id'] : 0;
$scrim = get_scrim_full($conn, $scrimId);
if (!$scrim) {
    header("Location: scrims.php");
    exit;
}
if (!registration_is_open($scrim)) {
    header("Location: scrim-details.php?id=" . $scrimId);
    exit;
}

$existing = $conn->prepare("SELECT * FROM payments WHERE user_id = ? AND scrim_id = ? ORDER BY id DESC LIMIT 1");
$existing->bind_param("ii", $_SESSION['user_id'], $scrimId);
$existing->execute();
$payment = $existing->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment | BGMI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-white">
<?php include_once __DIR__ . "/../includes/navbar.php"; ?>
<div class="mx-auto max-w-3xl px-6 py-10">
  <div class="rounded-[2rem] border border-slate-800 bg-slate-900 p-8">
    <h1 class="text-3xl font-bold">Upload Payment Proof</h1>
    <p class="mt-2 text-slate-400"><?php echo h($scrim['title']); ?> | Entry Fee: <?php echo format_money($scrim['entry_fee']); ?></p>
    <?php if ($payment): ?>
      <div class="mt-5 rounded-2xl bg-slate-950 p-4 text-slate-300">
        Latest payment status: <strong><?php echo strtoupper(h($payment['status'])); ?></strong>
      </div>
    <?php endif; ?>
    <div class="mt-6 rounded-2xl bg-slate-950 p-4">
      <p class="text-sm text-slate-400">Suggested UPI / payment handle</p>
      <p class="mt-2 text-xl font-bold text-amber-300">yourupi@upi</p>
    </div>
    <form action="../api/upload_payment.php" method="POST" enctype="multipart/form-data" class="mt-6 space-y-4">
      <input type="hidden" name="scrim_id" value="<?php echo $scrimId; ?>">
      <input type="text" name="transaction_ref" placeholder="Transaction ID / UTR" class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3">
      <input type="file" name="screenshot" required class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3">
      <button class="w-full rounded-2xl bg-amber-400 px-4 py-3 font-semibold text-black">Submit Payment Proof</button>
    </form>
  </div>
</div>
<?php include_once __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
