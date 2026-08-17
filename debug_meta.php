<?php
require_once 'includes/config.php';
$stmt = $pdo->query("SELECT * FROM pending_notifications WHERE notif_type = 'set_clan_member' LIMIT 10");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
unlink(__FILE__);
