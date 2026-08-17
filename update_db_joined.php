<?php
require_once 'includes/config.php';
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS joined_at DATETIME DEFAULT NULL");
    echo "Column joined_at added successfully";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
