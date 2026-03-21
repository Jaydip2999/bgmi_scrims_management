<?php
include_once __DIR__ . "/../includes/app.php";

$error = "";
$success = "";
if (isset($_POST['register'])) {
    $name = trim($_POST['name'] ?? '');
    $team = trim($_POST['team_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $uid = trim($_POST['bgmi_uid'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $identityConflict = find_user_identity_conflict($conn, $email, $phone, $uid);
        if ($identityConflict) {
            $error = identity_conflict_message($identityConflict['field']);
        } else {
            $role = 'player';
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, team_name, bgmi_uid, phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $name, $email, $hash, $role, $team, $uid, $phone);
            $stmt->execute();
            header("Location: login.php?registered=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register | BGMI Scrims</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-screen flex-col bg-slate-950 text-white">
<?php include_once __DIR__ . "/../includes/navbar.php"; ?>
<div class="mx-auto flex min-h-[calc(100vh-80px)] w-full max-w-6xl flex-1 items-center justify-center px-4 py-10">
  <div class="w-full max-w-3xl rounded-[2rem] border border-slate-800 bg-slate-900 p-8 md:p-10">
    <h1 class="text-3xl font-bold">Player / Team Registration</h1>
    <p class="mt-2 text-slate-400">Create your account once and use it to join scrims, upload payment proof, and track your results.</p>
    <?php if ($error !== ""): ?><p class="mt-5 rounded-2xl bg-rose-500/20 px-4 py-3 text-rose-200"><?php echo h($error); ?></p><?php endif; ?>
    <form method="POST" class="mt-6 grid gap-4 md:grid-cols-2">
      <label class="relative block">
        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-amber-300">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
        </span>
        <input type="text" name="name" placeholder="Owner / Player Name" required value="<?php echo h($name ?? ''); ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950 pl-12 pr-4 py-3">
      </label>
      <label class="relative block">
        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-amber-300">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19h16"/><path d="M5 19V9l7-4 7 4v10"/><path d="M9 19v-5h6v5"/></svg>
        </span>
        <input type="text" name="team_name" placeholder="Team Name" value="<?php echo h($team ?? ''); ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950 pl-12 pr-4 py-3">
      </label>
      <label class="relative block">
        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-amber-300">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg>
        </span>
        <input type="email" name="email" placeholder="Email" required value="<?php echo h($email ?? ''); ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950 pl-12 pr-4 py-3">
      </label>
      <label class="relative block">
        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-amber-300">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7l.5 3a2 2 0 0 1-.6 1.8l-2.2 2.2a16 16 0 0 0 6 6l2.2-2.2a2 2 0 0 1 1.8-.6l3 .5a2 2 0 0 1 1.7 2Z"/></svg>
        </span>
        <input type="text" name="phone" placeholder="Phone" value="<?php echo h($phone ?? ''); ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950 pl-12 pr-4 py-3">
      </label>
      <label class="relative block">
        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-amber-300">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 9h10"/><path d="M7 13h5"/></svg>
        </span>
        <input type="text" name="bgmi_uid" placeholder="BGMI UID" value="<?php echo h($uid ?? ''); ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950 pl-12 pr-4 py-3">
      </label>
      <div></div>
      <label class="relative block">
        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-amber-300">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V8a4 4 0 1 1 8 0v3"/></svg>
        </span>
        <input id="register-password" type="password" name="password" placeholder="Password" required class="w-full rounded-2xl border border-slate-700 bg-slate-950 pl-12 pr-12 py-3">
        <button type="button" data-toggle-password="register-password" class="absolute right-4 top-1/2 -translate-y-1/2 text-amber-300 transition hover:text-amber-200" aria-label="Show password">
          <svg data-eye-open xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg>
          <svg data-eye-closed xmlns="http://www.w3.org/2000/svg" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.4 5.1A11.4 11.4 0 0 1 12 5c6.5 0 10 7 10 7a17.7 17.7 0 0 1-4 4.9"/><path d="M6.7 6.7A17.2 17.2 0 0 0 2 12s3.5 7 10 7a10.7 10.7 0 0 0 5.3-1.4"/></svg>
        </button>
      </label>
      <label class="relative block">
        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-amber-300">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V8a4 4 0 1 1 8 0v3"/></svg>
        </span>
        <input id="register-confirm" type="password" name="confirm" placeholder="Confirm Password" required class="w-full rounded-2xl border border-slate-700 bg-slate-950 pl-12 pr-12 py-3">
        <button type="button" data-toggle-password="register-confirm" class="absolute right-4 top-1/2 -translate-y-1/2 text-amber-300 transition hover:text-amber-200" aria-label="Show password">
          <svg data-eye-open xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg>
          <svg data-eye-closed xmlns="http://www.w3.org/2000/svg" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.4 5.1A11.4 11.4 0 0 1 12 5c6.5 0 10 7 10 7a17.7 17.7 0 0 1-4 4.9"/><path d="M6.7 6.7A17.2 17.2 0 0 0 2 12s3.5 7 10 7a10.7 10.7 0 0 0 5.3-1.4"/></svg>
        </button>
      </label>
      <div class="md:col-span-2">
        <button type="submit" name="register" class="w-full rounded-2xl bg-amber-400 px-4 py-3 font-semibold text-black">Create Account</button>
      </div>
    </form>
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
