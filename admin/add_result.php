<?php
$id = isset($_GET['scrim_id']) ? (int) $_GET['scrim_id'] : 0;
header("Location: upload_result.php?id=" . $id);
exit;
