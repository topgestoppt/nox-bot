<?php
require_once 'includes/config.php';
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS login_notif TINYINT(1) DEFAULT 1");
    echo "Column added successfully";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
unlink(__FILE__);
?>
