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
<body class="flex min-h-screen flex-col bg-slate-950 text-white">
<?php include_once __DIR__ . "/../includes/navbar.php"; ?>
<div class="mx-auto flex min-h-[calc(100vh-80px)] w-full max-w-6xl flex-1 items-center justify-center px-4 py-10">
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
        <label class="relative block">
          <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-amber-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg>
          </span>
          <input type="email" name="email" placeholder="Email" required class="w-full rounded-2xl border border-slate-700 bg-slate-950 pl-12 pr-4 py-3">
        </label>
        <label class="relative block">
          <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-amber-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V8a4 4 0 1 1 8 0v3"/></svg>
          </span>
          <input id="login-password" type="password" name="password" placeholder="Password" required class="w-full rounded-2xl border border-slate-700 bg-slate-950 pl-12 pr-12 py-3">
          <button type="button" data-toggle-password="login-password" class="absolute right-4 top-1/2 -translate-y-1/2 text-amber-300 transition hover:text-amber-200" aria-label="Show password">
            <svg data-eye-open xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg data-eye-closed xmlns="http://www.w3.org/2000/svg" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.4 5.1A11.4 11.4 0 0 1 12 5c6.5 0 10 7 10 7a17.7 17.7 0 0 1-4 4.9"/><path d="M6.7 6.7A17.2 17.2 0 0 0 2 12s3.5 7 10 7a10.7 10.7 0 0 0 5.3-1.4"/></svg>
          </button>
        </label>
        <button type="submit" name="login" class="w-full rounded-2xl bg-amber-400 px-4 py-3 font-semibold text-black">Login</button>
      </form>
      <p class="mt-5 text-sm text-slate-400">No account? <a href="register.php" class="text-amber-300">Register here</a></p>
    </div>
  </div>
</div>
<?php include_once __DIR__ . "/../includes/footer.php"; ?>
<script>
document.querySelectorAll("[data-toggle-password]").forEach((button) => {
  const input = document.getElementById(button.dataset.togglePassword);
  if (!input) return;
  button.addEventListener("click", () => {
    const show = input.type === "password";
    input.type = show ? "text" : "password";
    button.querySelector("[data-eye-open]")?.classList.toggle("hidden", show);
    button.querySelector("[data-eye-closed]")?.classList.toggle("hidden", !show);
    button.setAttribute("aria-label", show ? "Hide password" : "Show password");
  });
});
</script>
</body>
</html>
