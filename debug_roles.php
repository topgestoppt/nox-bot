<?php
require_once 'includes/config.php';
$stmt = $pdo->query("SELECT discord_id, username, roles FROM users WHERE roles IS NOT NULL AND roles != '[]'");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Users with roles: " . count($users) . "\n";
foreach ($users as $u) {
    echo $u['username'] . " (" . $u['discord_id'] . "): " . $u['roles'] . "\n";
}
?>
