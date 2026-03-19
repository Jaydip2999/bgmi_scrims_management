<?php
include_once __DIR__ . "/../includes/app.php";

$error = "";
$flash = isset($_GET['registered']) ? "Account created successfully. Login to continue." : "";
if (isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || !password_verify($password, $user['password'])) {
        $error = "Invalid email or password.";
    } elseif ((int) $user['is_banned'] === 1) {
        $error = "Your account is banned from registrations.";
    } else {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        header("Location: " . ($user['role'] === 'admin' ? "../admin/dashboard.php" : "../index.php"));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login | BGMI Scrims</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-white">
<?php include_once __DIR__ . "/../includes/navbar.php"; ?>
<div class="mx-auto flex min-h-[calc(100vh-80px)] max-w-6xl items-center justify-center px-4 py-10">
  <div class="grid w-full max-w-4xl overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl md:grid-cols-2">
    <div class="bg-[linear-gradient(160deg,#f59e0b,#78350f)] p-10 text-black">
      <p class="text-sm font-semibold uppercase tracking-[0.3em]">Player Login</p>
      <h1 class="mt-4 text-4xl font-black leading-tight">Enter the lobby, check upcoming scrims, and track your match history.</h1>
      <p class="mt-4 text-sm text-black/80">Login keeps the flow simple: join matches, upload payment proof, and view leaderboard updates.</p>
    </div>
    <div class="p-8 md:p-10">
      <h2 class="text-2xl font-bold">Welcome Back</h2>
      <?php if ($flash !== ""): ?><p class="mt-4 rounded-2xl bg-emerald-500/20 px-4 py-3 text-emerald-200"><?php echo h($flash); ?></p><?php endif; ?>
      <?php if ($error !== ""): ?><p class="mt-4 rounded-2xl bg-rose-500/20 px-4 py-3 text-rose-200"><?php echo h($error); ?></p><?php endif; ?>
      <form method="POST" class="mt-6 space-y-4">
        <input type="email" name="email" placeholder="Email" required class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3">
        <input type="password" name="password" placeholder="Password" required class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3">
        <button type="submit" name="login" class="w-full rounded-2xl bg-amber-400 px-4 py-3 font-semibold text-black">Login</button>
      </form>
      <p class="mt-5 text-sm text-slate-400">No account? <a href="register.php" class="text-amber-300">Register here</a></p>
    </div>
  </div>
</div>
<?php include_once __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
