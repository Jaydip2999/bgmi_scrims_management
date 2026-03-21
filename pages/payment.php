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
$error = $_GET['error'] ?? '';

$upiId = 'yourupi@upi';
$upiName = 'BGMI Scrims';
$upiNote = $scrim['title'] . ' Entry Fee';
$upiAmount = number_format((float) $scrim['entry_fee'], 2, '.', '');
$upiLink = 'upi://pay?pa=' . rawurlencode($upiId)
    . '&pn=' . rawurlencode($upiName)
    . '&am=' . rawurlencode($upiAmount)
    . '&cu=INR'
    . '&tn=' . rawurlencode($upiNote);
$upiQrUrl = 'https://quickchart.io/qr?size=260&text=' . rawurlencode($upiLink);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment | BGMI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-screen flex-col bg-slate-950 text-white">
<?php include_once __DIR__ . "/../includes/navbar.php"; ?>
<div class="mx-auto w-full max-w-3xl flex-1 px-4 py-8 sm:px-6 sm:py-10">
  <div class="rounded-[2rem] border border-slate-800 bg-slate-900 p-5 sm:p-8">
    <h1 class="text-2xl font-bold sm:text-3xl">Upload Payment Proof</h1>
    <p class="mt-2 text-slate-400"><?php echo h($scrim['title']); ?> | Entry Fee: <?php echo format_money($scrim['entry_fee']); ?></p>
    <?php if ($error === 'identity_conflict'): ?>
      <div class="mt-5 rounded-2xl bg-rose-500/20 px-4 py-3 text-rose-200">
        This scrim already has a pending or approved registration linked to the same BGMI UID or phone number.
      </div>
    <?php endif; ?>
    <?php if ($payment): ?>
      <div class="mt-5 rounded-2xl bg-slate-950 p-4 text-slate-300">
        Latest payment status: <strong><?php echo strtoupper(h($payment['status'])); ?></strong>
      </div>
    <?php endif; ?>
    <div class="mt-6 grid gap-5 rounded-3xl border border-slate-800 bg-slate-950 p-4 sm:p-5 md:grid-cols-[220px_1fr]">
      <div class="rounded-2xl border border-slate-800 bg-white p-4">
        <img src="<?php echo h($upiQrUrl); ?>" alt="UPI QR Code" class="mx-auto h-full w-full rounded-xl object-contain">
      </div>
      <div class="flex flex-col justify-between gap-4">
        <div>
          <p class="text-sm uppercase tracking-[0.3em] text-slate-500">Scan And Pay</p>
          <h2 class="mt-2 text-2xl font-bold text-white">UPI QR Payment</h2>
          <p class="mt-2 text-sm text-slate-400">PhonePe, Google Pay, Paytm, BHIM ya kisi bhi UPI app se QR scan karke exact amount pay karo.</p>
        </div>
        <div class="grid gap-3 text-sm text-slate-300 sm:grid-cols-2">
          <div class="rounded-2xl border border-slate-800 bg-slate-900 p-4">
            <p class="text-slate-500">UPI ID</p>
            <p id="upi-id" class="mt-1 font-semibold text-amber-300"><?php echo h($upiId); ?></p>
          </div>
          <div class="rounded-2xl border border-slate-800 bg-slate-900 p-4">
            <p class="text-slate-500">Amount</p>
            <p class="mt-1 font-semibold text-emerald-300"><?php echo format_money($scrim['entry_fee']); ?></p>
          </div>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
          <button type="button" id="open-upi" data-upi-link="<?php echo h($upiLink); ?>" class="hidden rounded-2xl bg-amber-400 px-4 py-3 font-semibold text-black">Open UPI App</button>
          <button type="button" id="copy-upi" class="rounded-2xl border border-slate-700 px-4 py-3 font-semibold text-slate-200 hover:border-amber-300 hover:text-amber-300">Copy UPI ID</button>
        </div>
        <p id="upi-help" class="text-xs text-slate-500">UPI details ko apne real payment account ke hisaab se updated rakhna zaroori hai.</p>
      </div>
    </div>
    <form action="../api/upload_payment.php" method="POST" enctype="multipart/form-data" class="mt-6 space-y-4">
      <input type="hidden" name="scrim_id" value="<?php echo $scrimId; ?>">
      <input type="text" name="transaction_ref" placeholder="Transaction ID / UTR" class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3">
      <input type="file" name="screenshot" required class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm">
      <button class="w-full rounded-2xl bg-amber-400 px-4 py-3 font-semibold text-black">Submit Payment Proof</button>
    </form>
  </div>
</div>
<?php include_once __DIR__ . "/../includes/footer.php"; ?>
<script>
const openUpiButton = document.getElementById("open-upi");
const upiHelp = document.getElementById("upi-help");
const isMobileDevice = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);

if (openUpiButton) {
  if (isMobileDevice) {
    openUpiButton.classList.remove("hidden");
    openUpiButton.addEventListener("click", () => {
      const upiLink = openUpiButton.dataset.upiLink || "";
      if (!upiLink) return;

      if (/Android/i.test(navigator.userAgent)) {
        const intentLink = "intent://pay" + upiLink.replace("upi://pay", "") + "#Intent;scheme=upi;package=com.google.android.apps.nbu.paisa.user;end";
        window.location.href = intentLink;
        setTimeout(() => {
          window.location.href = upiLink;
        }, 300);
      } else {
        window.location.href = upiLink;
      }
    });
    if (upiHelp) {
      upiHelp.textContent = "Mobile par direct UPI app open karke payment kar sakte ho. Agar app open na ho, QR scan ya UPI ID copy karke payment complete karo.";
    }
  } else if (upiHelp) {
    upiHelp.textContent = "Payment ke liye QR scan karein ya UPI ID copy karke apni preferred UPI app me pay karein.";
  }
}

const copyUpiButton = document.getElementById("copy-upi");
if (copyUpiButton) {
  copyUpiButton.addEventListener("click", async () => {
    try {
      await navigator.clipboard.writeText(document.getElementById("upi-id")?.textContent?.trim() || "");
      copyUpiButton.textContent = "UPI ID Copied";
      setTimeout(() => {
        copyUpiButton.textContent = "Copy UPI ID";
      }, 1500);
    } catch (error) {
      copyUpiButton.textContent = "Copy Failed";
    }
  });
}
</script>
</body>
</html>
