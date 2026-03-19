<?php
include_once __DIR__ . "/includes/app.php";

$templates = $conn->query("SELECT * FROM scrim_templates WHERE is_active = 1");
while ($template = $templates->fetch_assoc()) {
    $targetDate = date('Y-m-d', strtotime('+' . (int) $template['create_days_ahead'] . ' day'));
    $matchTime = $targetDate . ' ' . $template['start_time'];

    $check = $conn->prepare("SELECT id FROM scrims WHERE title = ? AND match_time = ? LIMIT 1");
    $check->bind_param("ss", $template['title'], $matchTime);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        continue;
    }

    $prizePool = (float) $template['entry_fee'] * (int) $template['total_slots'];
    $status = 'open';
    $insert = $conn->prepare("INSERT INTO scrims (title, date, time, match_time, map, mode, entry_fee, slots, total_slots, prize_pool, registration_status, rules_text)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insert->bind_param(
        "ssssssdiidss",
        $template['title'],
        $targetDate,
        $template['start_time'],
        $matchTime,
        $template['map'],
        $template['mode'],
        $template['entry_fee'],
        $template['total_slots'],
        $template['total_slots'],
        $prizePool,
        $status,
        $template['rules_text']
    );
    $insert->execute();
    $scrimId = (int) $insert->insert_id;
    save_prize_distribution($conn, $scrimId, default_prize_distribution());
    create_broadcast($conn, 'New Daily Scrim Added', $template['title'] . " has been auto-created for " . date("d M Y, h:i A", strtotime($matchTime)) . ".");
}

create_match_reminders($conn);

echo "Cron run completed.";
