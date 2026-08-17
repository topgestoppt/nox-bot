<?php
include 'includes/header.php';
date_default_timezone_set('Europe/Berlin');

// Set MySQL session timezone
try {
    $pdo->exec("SET time_zone = '+02:00'");
} catch (Exception $e) {}

// Ensure member_logs table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS member_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(32) NOT NULL,
        action VARCHAR(50) NOT NULL,
        details TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS point_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(32) NOT NULL,
        action VARCHAR(50) NOT NULL,
        details TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS level_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(32) NOT NULL,
        action VARCHAR(50) NOT NULL,
        details TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS moderation_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(32) NOT NULL,
        action VARCHAR(50) NOT NULL,
        details TEXT DEFAULT NULL,
        admin_id VARCHAR(32) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Ensure warn_expires_at column exists in users table
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'warn_expires_at'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD warn_expires_at DATETIME DEFAULT NULL");
    }

    // Ensure is_undone column exists in moderation_logs table
    $stmt = $pdo->query("SHOW COLUMNS FROM moderation_logs LIKE 'is_undone'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE moderation_logs ADD is_undone TINYINT(1) DEFAULT 0");
    }
} catch (Exception $e) {}

// Moderation Access Check
$modRoles = ['1504744004686975107', '1504743897731956806', '1504741301831208960'];
$isModerator = $isAdmin;
if (!$isModerator && isset($userRoles) && is_array($userRoles)) {
    foreach ($modRoles as $role) {
        if (in_array($role, $userRoles)) {
            $isModerator = true;
            break;
        }
    }
}

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !$isModerator) {
    header('Location: index.php');
    exit;
}

// Handle log reset
if (isset($_POST['reset_logs']) && $webPermission === 'Administrator') {
    try {
        $category = $_POST['category'] ?? 'Giveaways';
        if ($category === 'Giveaways') {
            $pdo->exec("DELETE FROM giveaway_logs");
            $success_msg = "Alle Giveaway-Logs wurden erfolgreich gelöscht.";
        } elseif ($category === 'Members') {
            $pdo->exec("DELETE FROM member_logs");
            $success_msg = "Alle Mitglieder-Logs wurden erfolgreich gelöscht.";
        } elseif ($category === 'Points') {
            $pdo->exec("DELETE FROM point_logs");
            $success_msg = "Alle Nox-Points-Logs wurden erfolgreich gelöscht.";
        } elseif ($category === 'Levels') {
            $pdo->exec("DELETE FROM level_logs");
            $success_msg = "Alle Level-Logs wurden erfolgreich gelöscht.";
        } elseif ($category === 'Moderation') {
            $pdo->exec("DELETE FROM moderation_logs");
            $success_msg = "Alle Moderations-Logs wurden erfolgreich gelöscht.";
        }
    } catch (Exception $e) {
        $error = "Fehler beim Löschen der Logs: " . $e->getMessage();
    }
}

$category = $_GET['category'] ?? ($isAdmin ? 'Giveaways' : 'Moderation');

// Security Check: Moderators can ONLY see Moderation category
if (!$isAdmin && $category !== 'Moderation') {
    $category = 'Moderation';
}

$search = $_GET['search'] ?? '';
$filter_action = $_GET['action'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Fetch logs based on category
try {
    if ($category === 'Giveaways') {
        $query = "SELECT gl.*, u.username, u.web_permission FROM giveaway_logs gl LEFT JOIN users u ON gl.user_id = u.discord_id WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (gl.giveaway_id LIKE ? OR gl.details LIKE ? OR gl.user_id LIKE ? OR u.username LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($filter_action)) {
            $query .= " AND gl.action = ?";
            $params[] = $filter_action;
        }

        if ($sort === 'oldest') {
            $query .= " ORDER BY gl.created_at ASC";
        } else {
            $query .= " ORDER BY gl.created_at DESC";
        }

        $query .= " LIMIT 200";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        // Fetch unique actions for filter
        $stmtActions = $pdo->query("SELECT DISTINCT action FROM giveaway_logs ORDER BY action ASC");
        $availableActions = $stmtActions->fetchAll(PDO::FETCH_COLUMN);
    } elseif ($category === 'Members') {
        $query = "SELECT ml.*, u.username, u.web_permission FROM member_logs ml LEFT JOIN users u ON ml.user_id = u.discord_id WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (ml.details LIKE ? OR ml.user_id LIKE ? OR u.username LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($filter_action)) {
            $query .= " AND ml.action = ?";
            $params[] = $filter_action;
        }

        if ($sort === 'oldest') {
            $query .= " ORDER BY ml.created_at ASC";
        } else {
            $query .= " ORDER BY ml.created_at DESC";
        }

        $query .= " LIMIT 200";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        // Fetch unique actions for filter
        $stmtActions = $pdo->query("SELECT DISTINCT action FROM member_logs ORDER BY action ASC");
        $availableActions = $stmtActions->fetchAll(PDO::FETCH_COLUMN);
    } elseif ($category === 'Points') {
        $query = "SELECT pl.*, u.username, u.web_permission FROM point_logs pl LEFT JOIN users u ON pl.user_id = u.discord_id WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (pl.details LIKE ? OR pl.user_id LIKE ? OR u.username LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($filter_action)) {
            $query .= " AND pl.action = ?";
            $params[] = $filter_action;
        }

        if ($sort === 'oldest') {
            $query .= " ORDER BY pl.created_at ASC";
        } else {
            $query .= " ORDER BY pl.created_at DESC";
        }

        $query .= " LIMIT 200";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        // Fetch unique actions for filter
        $stmtActions = $pdo->query("SELECT DISTINCT action FROM point_logs ORDER BY action ASC");
        $availableActions = $stmtActions->fetchAll(PDO::FETCH_COLUMN);
    } elseif ($category === 'Levels') {
        $query = "SELECT ll.*, u.username, u.web_permission FROM level_logs ll LEFT JOIN users u ON ll.user_id = u.discord_id WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (ll.details LIKE ? OR ll.user_id LIKE ? OR u.username LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($filter_action)) {
            $query .= " AND ll.action = ?";
            $params[] = $filter_action;
        }

        if ($sort === 'oldest') {
            $query .= " ORDER BY ll.created_at ASC";
        } else {
            $query .= " ORDER BY ll.created_at DESC";
        }

        $query .= " LIMIT 200";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        // Fetch unique actions for filter
        $stmtActions = $pdo->query("SELECT DISTINCT action FROM level_logs ORDER BY action ASC");
        $availableActions = $stmtActions->fetchAll(PDO::FETCH_COLUMN);
    } elseif ($category === 'Moderation') {
        $query = "SELECT ml.*, u.username as victim_name, adm.username as admin_name 
                  FROM moderation_logs ml 
                  LEFT JOIN users u ON ml.user_id = u.discord_id 
                  LEFT JOIN users adm ON ml.admin_id = adm.discord_id 
                  WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (ml.details LIKE ? OR ml.user_id LIKE ? OR u.username LIKE ? OR ml.admin_id LIKE ? OR adm.username LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($filter_action)) {
            $query .= " AND ml.action = ?";
            $params[] = $filter_action;
        }

        if ($sort === 'oldest') {
            $query .= " ORDER BY ml.created_at ASC";
        } else {
            $query .= " ORDER BY ml.created_at DESC";
        }

        $query .= " LIMIT 200";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        // Fetch unique actions for filter
        $stmtActions = $pdo->query("SELECT DISTINCT action FROM moderation_logs ORDER BY action ASC");
        $availableActions = $stmtActions->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $logs = [];
    }
} catch (Exception $e) {
    $logs = [];
    $error = $e->getMessage();
}
?>

<section class="dashboard-section admin-page">
    <div class="dashboard-container">
        <aside class="dashboard-sidebar">
            <div class="user-info-card">
                <img src="https://cdn.discordapp.com/avatars/<?php echo $_SESSION['user']['id']; ?>/<?php echo $_SESSION['user']['avatar']; ?>.png" alt="Avatar">
                <div class="user-details">
                    <h3><?php echo htmlspecialchars($_SESSION['user']['username']); ?></h3>
                    <div class="user-status-badges">
                        <?php if (isset($highestRole) && $highestRole): ?>
                            <span class="status-tag" style="background: rgba(125, 64, 255, 0.1); color: #7d40ff; border: 1px solid rgba(125, 64, 255, 0.2); font-size: 10px; padding: 2px 8px; border-radius: 4px;">
                                <i class="fas fa-circle" style="font-size: 8px; margin-right: 5px;"></i> <?php echo htmlspecialchars($highestRole); ?>
                            </span>
                        <?php endif; ?>
                        <span class="badge-admin">Administrator</span>
                    </div>
                </div>
            </div>

            <nav class="dashboard-nav">
                <div class="nav-group">
                    <span class="nav-label">VERWALTUNG</span>
                    <a href="admin.php"><i class="fas fa-users"></i> Mitgliederverwaltung</a>
                    <?php if ($webPermission === 'Administrator' || $webPermission === 'GW-Manager'): ?>
                        <a href="admin_shop.php"><i class="fas fa-shopping-cart"></i> Punkte-Shop</a>
                        <a href="admin_redeem.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_redeem.php' ? 'active' : ''; ?>"><i class="fas fa-ticket-alt"></i> Code einlösen</a>
                        <?php if ($webPermission === 'Administrator'): ?>
                            <a href="admin_levels.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_levels.php' ? 'active' : ''; ?>"><i class="fas fa-layer-group"></i> Levelsystem</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="nav-group">
                    <span class="nav-label">LOGS</span>
                    <?php if ($isAdmin): ?>
                        <a href="admin_logs.php?category=Giveaways" class="<?php echo $category === 'Giveaways' ? 'active' : ''; ?>"><i class="fas fa-gift"></i> Giveaways</a>
                        <a href="admin_logs.php?category=Members" class="<?php echo $category === 'Members' ? 'active' : ''; ?>"><i class="fas fa-users-cog"></i> Mitglieder</a>
                        <a href="admin_logs.php?category=Levels" class="<?php echo $category === 'Levels' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Level</a>
                        <a href="admin_logs.php?category=Points" class="<?php echo $category === 'Points' ? 'active' : ''; ?>"><i class="fas fa-coins"></i> Nox-Points</a>
                    <?php endif; ?>
                    <a href="admin_logs.php?category=Moderation" class="<?php echo $category === 'Moderation' ? 'active' : ''; ?>"><i class="fas fa-shield-alt"></i> Moderation</a>
                </div>
                
                <div class="nav-group footer-nav">
                    <a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
                    <a href="login.php?action=logout" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </nav>
        </aside>

        <main class="dashboard-main">
            <div class="dashboard-header">
                <div class="header-top">
                    <span class="header-breadcrumb"><i class="fas fa-terminal"></i> Administration / Logs</span>
                    <h1 class="glow-text">System-Protokoll</h1>
                    <p class="header-desc">Echtzeit-Überwachung aller Aktivitäten innerhalb des <?php 
                        if ($category === 'Giveaways') echo 'Giveaway-Systems';
                        elseif ($category === 'Members') echo 'Mitglieder-Managements';
                        elseif ($category === 'Points') echo 'Nox-Points Systems';
                        elseif ($category === 'Levels') echo 'Level-Systems';
                        elseif ($category === 'Moderation') echo 'Moderations-Systems';
                        else echo 'Systems';
                    ?>.</p>
                </div>
                <?php if (isset($success_msg)): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
                <?php endif; ?>
            </div>

            <div class="content-box log-container">
                <div class="box-header-advanced">
                    <div class="header-info">
                        <div class="header-title-wrapper">
                            <i class="fas fa-list-ul header-icon"></i>
                            <div class="title-meta">
                                <h3><?php 
                                    if ($category === 'Giveaways') echo 'Giveaway History';
                                    elseif ($category === 'Members') echo 'Mitglieder Aktivitäten';
                                    elseif ($category === 'Points') echo 'Nox-Points Historie';
                                    elseif ($category === 'Levels') echo 'Level Aufstiege';
                                    elseif ($category === 'Moderation') echo 'Moderations Protokoll';
                                    else echo 'Aktivitäten';
                                ?></h3>
                                <span><?php echo count($logs); ?> Einträge geladen</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="header-actions">
                        <form action="admin_logs.php" method="GET" class="advanced-filter-bar">
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                            
                            <div class="search-input-wrapper">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="ID, User oder Details...">
                            </div>

                            <div class="select-wrapper">
                                <i class="fas fa-bolt"></i>
                                <select name="action">
                                    <option value="">Alle Aktionen</option>
                                    <?php foreach ($availableActions as $act): ?>
                                        <option value="<?php echo htmlspecialchars($act); ?>" <?php echo $filter_action === $act ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $act))); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="select-wrapper">
                                <i class="fas fa-sort-amount-down"></i>
                                <select name="sort">
                                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Neueste</option>
                                    <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Älteste</option>
                                </select>
                            </div>

                            <button type="submit" class="btn-filter-apply" title="Filter anwenden">
                                <i class="fas fa-sync-alt"></i>
                            </button>

                            <?php if (!empty($search) || !empty($filter_action)): ?>
                                <a href="admin_logs.php?category=<?php echo urlencode($category); ?>" class="btn-filter-reset" title="Filter zurücksetzen">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>
                        </form>

                        <?php if ($webPermission === 'Administrator'): ?>
                            <form action="admin_logs.php" method="POST" style="display: inline;" onsubmit="return confirm('Möchtest du wirklich alle Logs in dieser Kategorie löschen? Dies kann nicht rückgängig gemacht werden.');">
                                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                                <button type="submit" name="reset_logs" class="btn-reset-logs" title="Alle Logs löschen">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="modern-log-table">
                        <thead>
                            <tr>
                                <th width="140">Zeitpunkt</th>
                                <th width="150">Aktion</th>
                                <th width="180">Involvierter Akteur</th>
                                <th width="140">Referenz</th>
                                <th>Ereignis-Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="5" class="empty-table-state">
                                        <div class="empty-icon"><i class="fas fa-database"></i></div>
                                        <p>Keine Aktivitäten in diesem Zeitraum gefunden.</p>
                                        <?php if (!empty($search)): ?>
                                            <span class="search-hint">Versuche es mit anderen Suchbegriffen.</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): 
                                    $actionClass = 'default';
                                    $actionIcon = 'info-circle';
                                    
                                    switch($log['action']) {
                                        case 'start': 
                                            $actionClass = 'success'; 
                                            $actionIcon = 'play-circle';
                                            break;
                                        case 'end': 
                                            $actionClass = 'danger'; 
                                            $actionIcon = 'stop-circle';
                                            break;
                                        case 'reroll': 
                                            $actionClass = 'warning'; 
                                            $actionIcon = 'random';
                                            break;
                                        case 'points_award': 
                                            $actionClass = 'points'; 
                                            $actionIcon = 'coins';
                                            break;
                                        case 'join': 
                                            $actionClass = 'info'; 
                                            $actionIcon = 'user-plus';
                                            break;
                                        case 'edit':
                                        case 'update':
                                            $actionClass = 'edit';
                                            $actionIcon = 'edit';
                                            break;
                                        case 'remove':
                                        case 'delete':
                                            $actionClass = 'danger';
                                            $actionIcon = 'user-minus';
                                            break;
                                        case 'permission':
                                        case 'permission_change':
                                            $actionClass = 'warning';
                                            $actionIcon = 'shield-alt';
                                            break;
                                        case 'points_set':
                                        case 'points_add':
                                        case 'points_remove':
                                        case 'points_reset':
                                            $actionClass = 'points';
                                            $actionIcon = 'coins';
                                            break;
                                        case 'level_up':
                                            $actionClass = 'success';
                                            $actionIcon = 'arrow-up-right-dots';
                                            break;
                                        case 'level_edit':
                                        case 'level_delete':
                                        case 'settings_update':
                                            $actionClass = 'edit';
                                            $actionIcon = 'layer-group';
                                            break;
                                        case 'warn_1':
                                        case 'warn_2':
                                        case 'warn_3':
                                            $actionClass = 'warning';
                                            $actionIcon = 'triangle-exclamation';
                                            break;
                                        case 'kick':
                                        case 'ban':
                                            $actionClass = 'danger';
                                            $actionIcon = 'gavel';
                                            break;
                                    }
                                ?>
                                    <tr class="log-entry-row">
                                        <td>
                                            <div class="timestamp-box">
                                                <span class="date"><?php echo date('d.m.Y', strtotime($log['created_at'])); ?></span>
                                                <span class="time"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-badge-v2 <?php echo $actionClass; ?>">
                                                <i class="fas fa-<?php echo $actionIcon; ?>"></i>
                                                <span><?php echo htmlspecialchars(strtoupper($log['action'])); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="actor-card">
                                                <?php if ($log['user_id'] === 'SYSTEM'): ?>
                                                    <div class="system-actor">
                                                        <i class="fas fa-robot"></i>
                                                        <span>SYSTEM</span>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="user-actor">
                                                        <div class="actor-main">
                                                            <i class="fab fa-discord"></i>
                                                            <span class="actor-name"><?php 
                                                                if ($category === 'Moderation') {
                                                                    echo htmlspecialchars($log['admin_name'] ?? 'Unbekannt');
                                                                } else {
                                                                    echo htmlspecialchars($log['username'] ?? 'Unbekannt'); 
                                                                }
                                                            ?></span>
                                                            <?php if (in_array($log['web_permission'] ?? '', ['Administrator', 'GW-Manager', 'Bewerbung'])): ?>
                                                                <span class="badge-admin" style="font-size: 0.6rem; padding: 2px 6px; margin-left: 5px; vertical-align: middle;">Admin</span>
                                                            <?php endif; ?>
                                                            <span class="actor-tag">#<?php echo htmlspecialchars($category === 'Moderation' ? $log['admin_id'] : $log['user_id']); ?></span>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($category === 'Giveaways' && isset($log['giveaway_id']) && $log['giveaway_id']): ?>
                                                <div class="ref-badge">
                                                    <span class="hash">#</span>
                                                    <span class="id-val"><?php echo htmlspecialchars($log['giveaway_id']); ?></span>
                                                </div>
                                            <?php elseif ($category === 'Members'): ?>
                                                <div class="ref-badge member">
                                                    <i class="fas fa-id-card"></i>
                                                </div>
                                            <?php elseif ($category === 'Points'): ?>
                                                <div class="ref-badge member" style="border-color: rgba(234, 179, 8, 0.2); color: #eab308; background: rgba(234, 179, 8, 0.05);">
                                                    <i class="fas fa-coins"></i>
                                                </div>
                                            <?php elseif ($category === 'Levels'): ?>
                                                <div class="ref-badge member" style="border-color: rgba(168, 85, 247, 0.2); color: #a855f7; background: rgba(168, 85, 247, 0.05);">
                                                    <i class="fas fa-chart-line"></i>
                                                </div>
                                            <?php elseif ($category === 'Moderation'): ?>
                                                <div class="ref-badge member" style="border-color: rgba(239, 68, 68, 0.2); color: #ef4444; background: rgba(239, 68, 68, 0.05);">
                                                    <i class="fas fa-user-slash"></i>
                                                    <span style="font-size: 0.7rem; margin-left: 5px;"><?php echo htmlspecialchars($log['victim_name'] ?? $log['user_id']); ?></span>
                                                </div>
                                            <?php else: ?>
                                                <span class="no-ref">Keine Ref</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="event-details-text">
                                                <?php 
                                                    $details = htmlspecialchars($log['details']);
                                                    // Highlight certain patterns
                                                    $details = preg_replace('/<@(\d+)>/', '<span class="mention-pill"><i class="fab fa-discord"></i> $1</span>', $details);
                                                    $details = preg_replace('/(\d{2,})/', '<span class="num-highlight">$1</span>', $details);
                                                    echo $details;
                                                ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</section>

<style>
:root {
    --bg-darker: #0b0e14;
    --box-bg: rgba(17, 24, 39, 0.7);
    --border-color: rgba(255, 255, 255, 0.05);
    --primary-glow: rgba(168, 85, 247, 0.4);
    --secondary-glow: rgba(0, 185, 255, 0.4);
}

.log-container {
    padding: 0 !important;
    background: var(--box-bg) !important;
    border: 1px solid var(--border-color) !important;
    backdrop-filter: blur(10px);
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

/* Advanced Header */
.box-header-advanced {
    padding: 25px 30px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 30px;
    background: rgba(255,255,255,0.02);
}

.header-title-wrapper {
    display: flex;
    align-items: center;
    gap: 15px;
}

.header-icon {
    font-size: 1.8rem;
    color: var(--primary);
    filter: drop-shadow(0 0 5px var(--primary-glow));
}

.title-meta h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
}

.title-meta span {
    font-size: 0.85rem;
    color: var(--text-muted);
}

/* Filter Bar */
.advanced-filter-bar {
    display: flex;
    gap: 12px;
    align-items: center;
}

.search-input-wrapper {
    position: relative;
    min-width: 280px;
}

.search-input-wrapper i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    font-size: 0.9rem;
}

.search-input-wrapper input {
    width: 100%;
    background: rgba(0,0,0,0.2);
    border: 1px solid var(--border-color);
    padding: 10px 15px 10px 40px;
    border-radius: 10px;
    color: white;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.search-input-wrapper input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 10px var(--primary-glow);
    outline: none;
    background: rgba(0,0,0,0.3);
}

.select-wrapper {
    position: relative;
}

.select-wrapper i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    font-size: 0.8rem;
    pointer-events: none;
}

.select-wrapper select {
    background: rgba(0,0,0,0.2);
    border: 1px solid var(--border-color);
    padding: 10px 15px 10px 32px;
    border-radius: 10px;
    color: white;
    font-size: 0.85rem;
    appearance: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.select-wrapper select:hover {
    border-color: var(--primary);
}

.btn-filter-apply {
    background: var(--primary);
    border: none;
    color: white;
    width: 42px;
    height: 42px;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-filter-apply:hover {
    transform: rotate(180deg);
    filter: brightness(1.2);
    box-shadow: 0 0 15px var(--primary-glow);
}

.btn-filter-reset {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.2);
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

.btn-filter-reset:hover {
    background: #ef4444;
    color: white;
}

/* Modern Table Styles */
.table-wrapper {
    overflow-x: auto;
}

.modern-log-table {
    width: 100%;
    border-collapse: collapse;
}

.modern-log-table th {
    background: rgba(255,255,255,0.01);
    padding: 18px 25px;
    text-align: left;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--text-muted);
    border-bottom: 1px solid var(--border-color);
}

.log-entry-row {
    border-bottom: 1px solid var(--border-color);
    transition: all 0.2s ease;
}

.log-entry-row:hover {
    background: rgba(168, 85, 247, 0.03);
}

.log-entry-row td {
    padding: 20px 25px;
    vertical-align: middle;
}

/* Components */
.timestamp-box {
    display: flex;
    flex-direction: column;
}

.timestamp-box .date {
    font-size: 0.9rem;
    font-weight: 500;
}

.timestamp-box .time {
    font-size: 0.75rem;
    color: var(--primary);
    font-weight: 700;
}

.action-badge-v2 {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.5px;
}

.action-badge-v2.success { background: rgba(34, 197, 94, 0.1); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.2); }
.action-badge-v2.danger { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
.action-badge-v2.warning { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2); }
.action-badge-v2.info { background: rgba(59, 130, 246, 0.1); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.2); }
.action-badge-v2.points { background: rgba(234, 179, 8, 0.1); color: #eab308; border: 1px solid rgba(234, 179, 8, 0.2); }
.action-badge-v2.edit { background: rgba(168, 85, 247, 0.1); color: #a855f7; border: 1px solid rgba(168, 85, 247, 0.2); }
.action-badge-v2.default { background: rgba(156, 163, 175, 0.1); color: #9ca3af; border: 1px solid rgba(156, 163, 175, 0.2); }

.actor-card .system-actor {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #a855f7;
    font-weight: 700;
    font-size: 0.85rem;
    background: rgba(168, 85, 247, 0.1);
    padding: 5px 12px;
    border-radius: 6px;
}

.actor-card .user-actor {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.actor-card .actor-main {
    display: flex;
    align-items: center;
    gap: 8px;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
}

.actor-card .actor-main i {
    color: #5865f2;
    font-size: 0.9rem;
}

.actor-card .actor-tag {
    font-size: 0.75rem;
    color: var(--text-muted);
    font-family: monospace;
    opacity: 0.6;
}

.ref-badge {
    display: inline-flex;
    align-items: center;
    background: rgba(0, 185, 255, 0.05);
    border: 1px solid rgba(0, 185, 255, 0.2);
    border-radius: 6px;
    overflow: hidden;
}

.ref-badge .hash {
    background: rgba(0, 185, 255, 0.2);
    padding: 4px 8px;
    color: #00b9ff;
    font-weight: 800;
}

.ref-badge .id-val {
    padding: 4px 10px;
    color: #00b9ff;
    font-family: monospace;
    font-weight: 700;
}

.ref-badge.member {
    padding: 4px 12px;
    color: #00b9ff;
    font-size: 0.8rem;
}

.no-ref {
    color: var(--text-muted);
    font-size: 0.8rem;
    font-style: italic;
    opacity: 0.5;
}

.event-details-text {
    font-size: 0.9rem;
    color: #d1d5db;
    line-height: 1.5;
}

.mention-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(88, 101, 242, 0.15);
    color: #5865f2;
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: 600;
    font-family: monospace;
    font-size: 0.8rem;
}

.num-highlight {
    color: var(--secondary);
    font-weight: 700;
}

.empty-table-state {
    padding: 80px 0 !important;
    text-align: center;
}

.empty-icon {
    font-size: 4rem;
    color: var(--text-muted);
    opacity: 0.1;
    margin-bottom: 20px;
}

.empty-table-state p {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 5px;
}

.search-hint {
    font-size: 0.85rem;
    color: var(--text-muted);
}

.btn-reset-logs {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.2);
    color: #ef4444;
    width: 42px;
    height: 42px;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: 5px;
}

.btn-reset-logs:hover {
    background: #ef4444;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.alert {
    padding: 15px 25px;
    border-radius: 12px;
    margin: 20px 0;
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 0.95rem;
    font-weight: 500;
    animation: alertSlideDown 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.alert-success {
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.2);
    color: #22c55e;
}

.alert-danger {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.2);
    color: #ef4444;
}

@keyframes alertSlideDown {
    from { transform: translateY(-20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
</style>

<?php include 'includes/footer.php'; ?>
