<?php
require_once 'includes/config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting migration...<br>";

try {
    // Check joined_at
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'joined_at'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD joined_at DATETIME DEFAULT NULL");
        echo "Added joined_at column.<br>";
    } else {
        echo "joined_at column already exists.<br>";
    }

    // Check login_notif
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'login_notif'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD login_notif TINYINT(1) DEFAULT 1");
        echo "Added login_notif column.<br>";
    } else {
        echo "login_notif column already exists.<br>";
    }

    // Check roles
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'roles'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD roles TEXT DEFAULT NULL");
        echo "Added roles column.<br>";
    } else {
        echo "roles column already exists.<br>";
    }

    // Check is_clan_member
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_clan_member'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD is_clan_member TINYINT(1) DEFAULT 0");
        echo "Added is_clan_member column.<br>";
    } else {
        echo "is_clan_member column already exists.<br>";
    }

    // Check web_permission
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'web_permission'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD web_permission VARCHAR(50) DEFAULT 'User'");
        echo "Added web_permission column.<br>";
        
        // Set existing admins to 'Administrator' based on current logic
        $adminRoles = ['1499900998381080686', '1499900927723700375', '1499910122603020497', '1504599898698551336'];
        $stmtUsers = $pdo->query("SELECT discord_id, roles FROM users");
        while ($user = $stmtUsers->fetch()) {
            $userRoles = json_decode($user['roles'] ?? '[]', true);
            if (is_array($userRoles)) {
                foreach ($adminRoles as $role) {
                    if (in_array($role, $userRoles)) {
                        $pdo->prepare("UPDATE users SET web_permission = 'Administrator' WHERE discord_id = ?")->execute([$user['discord_id']]);
                        break;
                    }
                }
            }
        }
    } else {
        echo "web_permission column already exists.<br>";
    }
    
    // Check clan_stats table
    $pdo->exec("CREATE TABLE IF NOT EXISTS clan_stats (
        stat_key VARCHAR(50) PRIMARY KEY,
        stat_value VARCHAR(255) NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Ensured clan_stats table exists.<br>";
    
    // Check moderation_logs table
    $pdo->exec("CREATE TABLE IF NOT EXISTS moderation_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(50) NOT NULL,
        admin_id VARCHAR(50) NOT NULL,
        action VARCHAR(50) NOT NULL,
        details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Ensured moderation_logs table exists.<br>";

    // Force add warn_expires_at column to be safe
    try {
        $pdo->exec("ALTER TABLE users ADD warn_expires_at DATETIME DEFAULT NULL");
        echo "Added warn_expires_at column.<br>";
    } catch (Exception $e) {
        echo "Note: warn_expires_at might already exist or: " . $e->getMessage() . "<br>";
    }

    // Add is_undone to moderation_logs
    try {
        $pdo->exec("ALTER TABLE moderation_logs ADD is_undone TINYINT(1) DEFAULT 0");
        echo "Added is_undone column to moderation_logs.<br>";
    } catch (Exception $e) {
        echo "Note: is_undone might already exist.<br>";
    }
    
    echo "Migration finished successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
