<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . "/db.php";

function app_query(mysqli $conn, string $sql): void
{
    $conn->query($sql);
}

function ensure_column(mysqli $conn, string $table, string $column, string $definition): void
{
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($result->num_rows === 0) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

function ensure_schema(mysqli $conn): void
{
    app_query($conn, "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(180) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin','player') NOT NULL DEFAULT 'player',
        team_name VARCHAR(120) DEFAULT NULL,
        bgmi_uid VARCHAR(80) DEFAULT NULL,
        phone VARCHAR(30) DEFAULT NULL,
        is_banned TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    app_query($conn, "CREATE TABLE IF NOT EXISTS scrims (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(160) NOT NULL,
        date DATE DEFAULT NULL,
        time TIME DEFAULT NULL,
        match_time DATETIME DEFAULT NULL,
        map VARCHAR(80) DEFAULT NULL,
        mode ENUM('Solo','Duo','Squad') NOT NULL DEFAULT 'Squad',
        entry_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
        slots INT NOT NULL DEFAULT 0,
        total_slots INT NOT NULL DEFAULT 0,
        prize_pool DECIMAL(10,2) NOT NULL DEFAULT 0,
        registration_status ENUM('open','closed','full') NOT NULL DEFAULT 'open',
        room_id VARCHAR(120) DEFAULT NULL,
        room_password VARCHAR(120) DEFAULT NULL,
        rules_text TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    app_query($conn, "CREATE TABLE IF NOT EXISTS prizes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        scrim_id INT NOT NULL,
        rank_position INT NOT NULL,
        percentage DECIMAL(5,2) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_scrim_rank (scrim_id, rank_position),
        CONSTRAINT fk_prizes_scrim FOREIGN KEY (scrim_id) REFERENCES scrims(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    app_query($conn, "CREATE TABLE IF NOT EXISTS bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        scrim_id INT NOT NULL,
        slot_number INT DEFAULT NULL,
        status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user_scrim (user_id, scrim_id),
        CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_bookings_scrim FOREIGN KEY (scrim_id) REFERENCES scrims(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    app_query($conn, "CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        scrim_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        screenshot VARCHAR(255) DEFAULT NULL,
        transaction_ref VARCHAR(120) DEFAULT NULL,
        status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_payments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_payments_scrim FOREIGN KEY (scrim_id) REFERENCES scrims(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    app_query($conn, "CREATE TABLE IF NOT EXISTS results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        scrim_id INT NOT NULL,
        user_id INT NOT NULL,
        kills INT NOT NULL DEFAULT 0,
        rank_position INT NOT NULL DEFAULT 0,
        points INT NOT NULL DEFAULT 0,
        payout_status ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid',
        payout_transaction_id VARCHAR(120) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_result (scrim_id, user_id),
        CONSTRAINT fk_results_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_results_scrim FOREIGN KEY (scrim_id) REFERENCES scrims(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    app_query($conn, "CREATE TABLE IF NOT EXISTS scrim_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(160) NOT NULL,
        mode ENUM('Solo','Duo','Squad') NOT NULL DEFAULT 'Squad',
        map VARCHAR(80) DEFAULT NULL,
        entry_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
        total_slots INT NOT NULL DEFAULT 0,
        rules_text TEXT DEFAULT NULL,
        start_time TIME NOT NULL,
        create_days_ahead INT NOT NULL DEFAULT 1,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    app_query($conn, "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        scrim_id INT DEFAULT NULL,
        type ENUM('broadcast','reminder','system') NOT NULL DEFAULT 'system',
        title VARCHAR(160) NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        reminder_key VARCHAR(190) DEFAULT NULL,
        UNIQUE KEY uniq_reminder_key (reminder_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    ensure_column($conn, "scrims", "match_time", "DATETIME DEFAULT NULL");
    ensure_column($conn, "scrims", "mode", "ENUM('Solo','Duo','Squad') NOT NULL DEFAULT 'Squad'");
    ensure_column($conn, "scrims", "total_slots", "INT NOT NULL DEFAULT 0");
    ensure_column($conn, "scrims", "prize_pool", "DECIMAL(10,2) NOT NULL DEFAULT 0");
    ensure_column($conn, "scrims", "registration_status", "ENUM('open','closed','full') NOT NULL DEFAULT 'open'");
    ensure_column($conn, "scrims", "rules_text", "TEXT DEFAULT NULL");
    ensure_column($conn, "scrims", "room_id", "VARCHAR(120) DEFAULT NULL");
    ensure_column($conn, "scrims", "room_password", "VARCHAR(120) DEFAULT NULL");
    ensure_column($conn, "users", "team_name", "VARCHAR(120) DEFAULT NULL");
    ensure_column($conn, "users", "bgmi_uid", "VARCHAR(80) DEFAULT NULL");
    ensure_column($conn, "users", "phone", "VARCHAR(30) DEFAULT NULL");
    ensure_column($conn, "users", "is_banned", "TINYINT(1) NOT NULL DEFAULT 0");
    ensure_column($conn, "payments", "transaction_ref", "VARCHAR(120) DEFAULT NULL");
    ensure_column($conn, "bookings", "slot_number", "INT DEFAULT NULL");
    ensure_column($conn, "results", "payout_status", "ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid'");
    ensure_column($conn, "results", "payout_transaction_id", "VARCHAR(120) DEFAULT NULL");
    ensure_column($conn, "notifications", "reminder_key", "VARCHAR(190) DEFAULT NULL");

    $conn->query("UPDATE scrims SET total_slots = slots WHERE total_slots = 0 AND slots IS NOT NULL");
    $conn->query("UPDATE scrims SET match_time = CONCAT(date, ' ', time) WHERE match_time IS NULL AND date IS NOT NULL AND time IS NOT NULL");
    $conn->query("UPDATE scrims SET prize_pool = entry_fee * total_slots WHERE prize_pool = 0");
}

ensure_schema($conn);

function scrim_registration_deadline(array $scrim): ?int
{
    if (empty($scrim['match_time'])) {
        return null;
    }
    $matchTs = strtotime((string) $scrim['match_time']);
    if ($matchTs === false) {
        return null;
    }
    return $matchTs - 600;
}

function registration_is_open(array $scrim): bool
{
    $deadline = scrim_registration_deadline($scrim);
    if ($deadline !== null && time() >= $deadline) {
        return false;
    }
    if (($scrim['registration_status'] ?? '') !== 'open') {
        return false;
    }
    if (isset($scrim['available_slots']) && (int) $scrim['available_slots'] <= 0) {
        return false;
    }
    return true;
}

function refresh_scrim_registration_statuses(mysqli $conn): void
{
    $rows = $conn->query("SELECT s.id, s.match_time, s.registration_status, s.total_slots,
        (SELECT COUNT(*) FROM bookings b WHERE b.scrim_id = s.id AND b.status='approved') AS approved_count
        FROM scrims s");

    while ($scrim = $rows->fetch_assoc()) {
        $now = time();
        $matchTs = !empty($scrim['match_time']) ? strtotime((string) $scrim['match_time']) : null;
        $newStatus = $scrim['registration_status'];

        if ($matchTs !== null && $matchTs - 600 <= $now) {
            $newStatus = 'closed';
        } elseif ((int) $scrim['approved_count'] >= (int) $scrim['total_slots'] && (int) $scrim['total_slots'] > 0) {
            $newStatus = 'full';
        } elseif ($scrim['registration_status'] !== 'closed') {
            $newStatus = 'open';
        }

        if ($newStatus !== $scrim['registration_status']) {
            $stmt = $conn->prepare("UPDATE scrims SET registration_status = ? WHERE id = ?");
            $stmt->bind_param("si", $newStatus, $scrim['id']);
            $stmt->execute();
        }
    }
}

refresh_scrim_registration_statuses($conn);

function require_login(): void
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../pages/login.php");
        exit;
    }
}

function require_admin(): void
{
    require_login();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        header("Location: ../pages/scrims.php");
        exit;
    }
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function format_money($amount): string
{
    return "Rs " . number_format((float) $amount, 2);
}

function default_prize_distribution(): array
{
    return [
        1 => 50,
        2 => 30,
        3 => 20,
    ];
}

function sync_scrim_meta(mysqli $conn, int $scrimId): void
{
    $stmt = $conn->prepare("SELECT total_slots, entry_fee, registration_status,
        (SELECT COUNT(*) FROM bookings WHERE scrim_id = ? AND status = 'approved') AS approved_count
        FROM scrims WHERE id = ?");
    $stmt->bind_param("ii", $scrimId, $scrimId);
    $stmt->execute();
    $scrim = $stmt->get_result()->fetch_assoc();
    if (!$scrim) {
        return;
    }

    $deadline = scrim_registration_deadline($scrim);
    if ($deadline !== null && time() >= $deadline) {
        $status = 'closed';
    } else {
        $status = ((int) $scrim['approved_count'] >= (int) $scrim['total_slots'] && (int) $scrim['total_slots'] > 0) ? 'full' : 'open';
    }
    $pool = (float) $scrim['entry_fee'] * (int) $scrim['total_slots'];

    if ($scrim['registration_status'] !== 'closed') {
        $update = $conn->prepare("UPDATE scrims SET prize_pool = ?, registration_status = ? WHERE id = ?");
        $update->bind_param("dsi", $pool, $status, $scrimId);
    } else {
        $update = $conn->prepare("UPDATE scrims SET prize_pool = ? WHERE id = ?");
        $update->bind_param("di", $pool, $scrimId);
    }
    $update->execute();
}

function save_prize_distribution(mysqli $conn, int $scrimId, array $distribution): void
{
    $scrimStmt = $conn->prepare("SELECT prize_pool FROM scrims WHERE id = ?");
    $scrimStmt->bind_param("i", $scrimId);
    $scrimStmt->execute();
    $scrim = $scrimStmt->get_result()->fetch_assoc();
    if (!$scrim) {
        return;
    }

    $delete = $conn->prepare("DELETE FROM prizes WHERE scrim_id = ?");
    $delete->bind_param("i", $scrimId);
    $delete->execute();

    $insert = $conn->prepare("INSERT INTO prizes (scrim_id, rank_position, percentage, amount) VALUES (?, ?, ?, ?)");
    foreach ($distribution as $rank => $percentage) {
        $amount = ((float) $scrim['prize_pool'] * (float) $percentage) / 100;
        $insert->bind_param("iidd", $scrimId, $rank, $percentage, $amount);
        $insert->execute();
    }
}

function get_next_slot(mysqli $conn, int $scrimId): ?int
{
    $stmt = $conn->prepare("SELECT total_slots FROM scrims WHERE id = ?");
    $stmt->bind_param("i", $scrimId);
    $stmt->execute();
    $scrim = $stmt->get_result()->fetch_assoc();
    if (!$scrim) {
        return null;
    }

    $used = [];
    $rows = $conn->prepare("SELECT slot_number FROM bookings WHERE scrim_id = ? AND status = 'approved' AND slot_number IS NOT NULL ORDER BY slot_number ASC");
    $rows->bind_param("i", $scrimId);
    $rows->execute();
    $result = $rows->get_result();
    while ($row = $result->fetch_assoc()) {
        $used[(int) $row['slot_number']] = true;
    }

    for ($slot = 1; $slot <= (int) $scrim['total_slots']; $slot++) {
        if (!isset($used[$slot])) {
            return $slot;
        }
    }

    return null;
}

function calculate_points(int $kills, int $rank): int
{
    $points = $kills * 2;
    if ($rank === 1) {
        return $points + 20;
    }
    if ($rank === 2) {
        return $points + 14;
    }
    if ($rank === 3) {
        return $points + 10;
    }
    if ($rank <= 10) {
        return $points + 5;
    }

    return $points;
}

function get_scrim_full(mysqli $conn, int $scrimId): ?array
{
    $stmt = $conn->prepare("SELECT s.*,
        COALESCE(s.total_slots, s.slots) AS slot_cap,
        (
            SELECT COUNT(*) FROM bookings b
            WHERE b.scrim_id = s.id AND b.status = 'approved'
        ) AS approved_slots,
        (
            SELECT COUNT(*) FROM bookings b
            WHERE b.scrim_id = s.id
        ) AS total_registrations
        FROM scrims s
        WHERE s.id = ?");
    $stmt->bind_param("i", $scrimId);
    $stmt->execute();
    $scrim = $stmt->get_result()->fetch_assoc();
    if (!$scrim) {
        return null;
    }
    $scrim['available_slots'] = max(0, (int) $scrim['slot_cap'] - (int) $scrim['approved_slots']);
    return $scrim;
}

function room_visible(array $scrim): bool
{
    if (empty($scrim['match_time']) || empty($scrim['room_id']) || empty($scrim['room_password'])) {
        return false;
    }
    return strtotime($scrim['match_time']) - time() <= 600;
}

function player_label(array $row): string
{
    return trim(($row['team_name'] ?? '') ?: ($row['name'] ?? 'Player'));
}

function fetch_all_assoc(mysqli_stmt $stmt): array
{
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function create_notification(mysqli $conn, ?int $userId, ?int $scrimId, string $type, string $title, string $message, ?string $reminderKey = null): void
{
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, scrim_id, type, title, message, reminder_key)
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $userId, $scrimId, $type, $title, $message, $reminderKey);
    try {
        $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() !== 1062) {
            throw $e;
        }
    }
}

function create_broadcast(mysqli $conn, string $title, string $message): void
{
    create_notification($conn, null, null, 'broadcast', $title, $message, null);
}

function unread_notification_count(mysqli $conn, ?int $userId): int
{
    if (!$userId) {
        return 0;
    }
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM notifications WHERE is_read = 0 AND (user_id IS NULL OR user_id = ?)");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (int) ($row['total'] ?? 0);
}

function fetch_notifications(mysqli $conn, ?int $userId, int $limit = 8): array
{
    if (!$userId) {
        return [];
    }
    $stmt = $conn->prepare("SELECT * FROM notifications
        WHERE user_id IS NULL OR user_id = ?
        ORDER BY created_at DESC
        LIMIT ?");
    $stmt->bind_param("ii", $userId, $limit);
    return fetch_all_assoc($stmt);
}

function create_match_reminders(mysqli $conn): void
{
    $query = $conn->query("SELECT s.id AS scrim_id, s.title, s.match_time, b.user_id
        FROM scrims s
        JOIN bookings b ON b.scrim_id = s.id AND b.status = 'approved'
        WHERE s.match_time IS NOT NULL");

    while ($row = $query->fetch_assoc()) {
        $matchTs = strtotime((string) $row['match_time']);
        if ($matchTs === false) {
            continue;
        }
        $diff = $matchTs - time();
        if ($diff > 0 && $diff <= 1800) {
            $key = 'reminder:' . $row['scrim_id'] . ':' . $row['user_id'] . ':' . date('YmdHi', $matchTs);
            $message = $row['title'] . " starts at " . date("d M Y, h:i A", $matchTs) . ". Room details unlock 10 minutes before match time.";
            create_notification($conn, (int) $row['user_id'], (int) $row['scrim_id'], 'reminder', 'Match Reminder', $message, $key);
        }
    }
}
?>
