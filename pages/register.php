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
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = "Email already registered.";
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
<body class="min-h-screen bg-slate-950 text-white">
<?php include_once __DIR__ . "/../includes/navbar.php"; ?>
<div class="mx-auto flex min-h-[calc(100vh-80px)] max-w-6xl items-center justify-center px-4 py-10">
  <div class="w-full max-w-3xl rounded-[2rem] border border-slate-800 bg-slate-900 p-8 md:p-10">
    <h1 class="text-3xl font-bold">Player / Team Registration</h1>
    <p class="mt-2 text-slate-400">Create your account once and use it to join scrims, upload payment proof, and track your results.</p>
    <?php if ($error !== ""): ?><p class="mt-5 rounded-2xl bg-rose-500/20 px-4 py-3 text-rose-200"><?php echo h($error); ?></p><?php endif; ?>
    <form method="POST" class="mt-6 grid gap-4 md:grid-cols-2">
      <input type="text" name="name" placeholder="Owner / Player Name" required class="rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3">
      <input type="text" name="team_name" placeholder="Team Name" class="rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3">
      <input type="email" name="email" placeholder="Email" required class="rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3">
      <input type="text" name="phone" placeholder="Phone" class="rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3">
      <input type="text" name="bgmi_uid" placeholder="BGMI UID" class="rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3">
      <div></div>
      <input type="password" name="password" placeholder="Password" required class="rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3">
      <input type="password" name="confirm" placeholder="Confirm Password" required class="rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3">
      <div class="md:col-span-2">
        <button type="submit" name="register" class="w-full rounded-2xl bg-amber-400 px-4 py-3 font-semibold text-black">Create Account</button>
      </div>
    </form>
  </div>
</div>
<?php include_once __DIR__ . "/../includes/footer.php"; ?>
</body>
</html>
