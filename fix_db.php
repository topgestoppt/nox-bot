<?php
require_once 'includes/config.php';
try {
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns: " . implode(", ", $columns) . "\n";
    
    // Explicitly add if missing
    if (!in_array('is_clan_member', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_clan_member TINYINT(1) DEFAULT 0 AFTER roles");
        echo "Added is_clan_member\n";
    }
    if (!in_array('roles', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN roles TEXT DEFAULT NULL AFTER access_token");
        echo "Added roles\n";
    }
    if (!in_array('points', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN points INT DEFAULT 0 AFTER is_clan_member");
        echo "Added points\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
