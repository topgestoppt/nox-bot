<?php
include 'includes/header.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

// Fetch Stats
$stats = [];
$stmt = $pdo->query("SELECT stat_key, stat_value FROM clan_stats");
while ($row = $stmt->fetch()) {
    $stats[$row['stat_key']] = $row['stat_value'];
}

// Fetch Live Clan Stats
try {
    $stmtClan = $pdo->query("SELECT SUM(points) as total_points, COUNT(*) as member_count FROM users WHERE is_clan_member = 1");
    $clanLive = $stmtClan->fetch();
    $totalClanPoints = $clanLive['total_points'] ?? 0;
    $memberCount = $clanLive['member_count'] ?? 0;
} catch (Exception $e) {
    $totalClanPoints = $stats['total_points'] ?? 0;
    $memberCount = $stats['member_count'] ?? 0;
}

// Fetch User DB Data
try {
    $stmt = $pdo->prepare("SELECT points FROM users WHERE discord_id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $dbUser = $stmt->fetch();
    $userPoints = $dbUser['points'] ?? 0;
    
    // joined_at und login_notif separat abfragen um Absturz bei fehlender Spalte zu vermeiden
    $userJoinedAt = null;
    $loginNotif = 1;
    $isClanMember = 0;
    try {
        $stmt2 = $pdo->prepare("SELECT joined_at, login_notif, is_clan_member, clan_meta FROM users WHERE discord_id = ?");
        $stmt2->execute([$_SESSION['user']['id']]);
        $dbUser2 = $stmt2->fetch();
        $userJoinedAt = $dbUser2['joined_at'] ?? null;
        $isClanMember = $dbUser2['is_clan_member'] ?? 0;
        $userClanMeta = json_decode($dbUser2['clan_meta'] ?? '{}', true);
        
        // Sicherstellen, dass die Tabelle existiert (Auto-Fix)
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_notifications (
            discord_id VARCHAR(32) NOT NULL,
            module_key VARCHAR(50) NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (discord_id, module_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS pending_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            discord_id VARCHAR(32) NOT NULL,
            username VARCHAR(100) NOT NULL,
            module_key VARCHAR(50) NOT NULL,
            notif_type VARCHAR(20) DEFAULT 'login',
            meta_value TEXT DEFAULT NULL,
            processed TINYINT(1) DEFAULT 0,
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
        
        // Spalten-Migration (Self-Healing)
        try { $pdo->exec("ALTER TABLE pending_notifications ADD COLUMN notif_type VARCHAR(20) DEFAULT 'login' AFTER module_key"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE pending_notifications MODIFY COLUMN meta_value TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE pending_notifications DROP COLUMN force_send"); } catch (Exception $e) {}
        
        // Users Table Migration
        try { $pdo->exec("ALTER TABLE users ADD COLUMN roles TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN is_clan_member TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN points INT DEFAULT 0"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN is_on_discord TINYINT(1) DEFAULT 1"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN joined_at DATETIME DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN login_notif TINYINT(1) DEFAULT 1"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN clan_meta TEXT DEFAULT NULL"); } catch (Exception $e) {}
        
        // Smarter Check: Erst in user_notifications schauen, dann Fallback auf users Spalte
        $stmtNotif = $pdo->prepare("SELECT is_active FROM user_notifications WHERE discord_id = ? AND module_key = 'registration'");
        $stmtNotif->execute([$_SESSION['user']['id']]);
        $notifResult = $stmtNotif->fetch();
        
        if ($notifResult !== false) {
            $loginNotif = (int)$notifResult['is_active'];
        } else {
            $loginNotif = isset($dbUser2['login_notif']) ? (int)$dbUser2['login_notif'] : 1;
        }

        // Shop Purchase Notif
        $stmtShop = $pdo->prepare("SELECT is_active FROM user_notifications WHERE discord_id = ? AND module_key IN ('shop_buy', 'shop_purchase') ORDER BY module_key DESC LIMIT 1");
        $stmtShop->execute([$_SESSION['user']['id']]);
        $shopNotifResult = $stmtShop->fetch();
        $shopBuyNotif = ($shopNotifResult !== false) ? (int)$shopNotifResult['is_active'] : 1;

        // Code Redeem Notif
        $stmtRedeem = $pdo->prepare("SELECT is_active FROM user_notifications WHERE discord_id = ? AND module_key = 'code_redeem'");
        $stmtRedeem->execute([$_SESSION['user']['id']]);
        $redeemNotifResult = $stmtRedeem->fetch();
        $redeemNotif = ($redeemNotifResult !== false) ? (int)$redeemNotifResult['is_active'] : 1;

        // Level Up Notif
        $stmtLevelNotif = $pdo->prepare("SELECT is_active FROM user_notifications WHERE discord_id = ? AND module_key = 'level_up'");
        $stmtLevelNotif->execute([$_SESSION['user']['id']]);
        $levelNotifResult = $stmtLevelNotif->fetch();
        $levelUpNotif = ($levelNotifResult !== false) ? (int)$levelNotifResult['is_active'] : 1;

        // Giveaway Notif
        $stmtGiveawayNotif = $pdo->prepare("SELECT is_active FROM user_notifications WHERE discord_id = ? AND module_key = 'giveaway'");
        $stmtGiveawayNotif->execute([$_SESSION['user']['id']]);
        $giveawayNotifResult = $stmtGiveawayNotif->fetch();
        $giveawayNotif = ($giveawayNotifResult !== false) ? (int)$giveawayNotifResult['is_active'] : 1;

        // Sound Setting
        $stmtSound = $pdo->prepare("SELECT is_active FROM user_notifications WHERE discord_id = ? AND module_key = 'ui_sounds'");
        $stmtSound->execute([$_SESSION['user']['id']]);
        $soundNotifResult = $stmtSound->fetch();
        $uiSoundsActive = ($soundNotifResult !== false) ? (int)$soundNotifResult['is_active'] : 1;

        // Fetch Level Data
        $stmtLevel = $pdo->prepare("SELECT xp, level FROM users WHERE discord_id = ?");
        $stmtLevel->execute([$_SESSION['user']['id']]);
        $userLevelData = $stmtLevel->fetch();
        $currentXP = $userLevelData['xp'] ?? 0;
        $currentLevel = $userLevelData['level'] ?? 0;

        // Fetch Next Level
        $stmtNextLevel = $pdo->prepare("SELECT * FROM levels WHERE level_number > ? ORDER BY level_number ASC LIMIT 1");
        $stmtNextLevel->execute([$currentLevel]);
        $nextLevel = $stmtNextLevel->fetch();

        if ($nextLevel) {
            $xpForNextLevel = $nextLevel['xp_required'];
            $progressPercent = ($xpForNextLevel > 0) ? min(100, ($currentXP / $xpForNextLevel) * 100) : 0;
            $xpDisplay = number_format($currentXP, 1) . " / " . number_format($xpForNextLevel, 0) . " XP";
        } else {
            // Max Level reached
            $xpForNextLevel = $currentXP;
            $progressPercent = 100;
            $xpDisplay = number_format($currentXP, 1) . " / ∞ XP";
        }

        // Reward Status Check
        $stmtReward = $pdo->prepare("SELECT last_claim FROM user_rewards WHERE discord_id = ?");
        $stmtReward->execute([$_SESSION['user']['id']]);
        $lastClaimDate = $stmtReward->fetchColumn();
        $hasRewardPending = ($lastClaimDate !== date('Y-m-d'));

        // Moderation Access Check
        $modRoles = ['1504744004686975107', '1504743897731956806', '1504741301831208960'];
        $canModerate = $isAdmin;
        if (!$canModerate && isset($userRoles) && is_array($userRoles)) {
            foreach ($modRoles as $role) {
                if (in_array($role, $userRoles)) {
                    $canModerate = true;
                    break;
                }
            }
        }

        // Kick/Ban specific Check (Nachtwache+)
        $kickBanRoles = ['1504743897731956806', '1504741301831208960'];
        // Nur echte Admins oder Nachtwache+ haben Kick/Ban Rechte
        $canKickBan = ($webPermission === 'Administrator'); 
        if (!$canKickBan && isset($userRoles) && is_array($userRoles)) {
            // Check higher roles from adminRoles too just in case
            $allKickBanRoles = array_merge($kickBanRoles, $adminRoles ?? []);
            foreach ($allKickBanRoles as $role) {
                if (in_array($role, $userRoles)) {
                    $canKickBan = true;
                    break;
                }
            }
        }

    } catch (Exception $e) {
        // Spalte fehlt vermutlich, versuche sie anzulegen (kompatibel)
        $pdo->exec("ALTER TABLE users ADD joined_at DATETIME DEFAULT NULL");
        $pdo->exec("ALTER TABLE users ADD login_notif TINYINT(1) DEFAULT 1");
    }
} catch (Exception $e) {
    $userPoints = 0;
    $userJoinedAt = null;
    $loginNotif = 1;
}
?>

<style>
.points-display-box {
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(10, 10, 25, 0.3) 100%) !important;
    border: 1px solid rgba(168, 85, 247, 0.2) !important;
    position: relative;
    overflow: hidden;
}

.points-visual {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 10px 0;
    gap: 15px;
}

.points-icon-large {
    width: 60px;
    height: 60px;
    background: rgba(139, 92, 246, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #a855f7;
    font-size: 1.8rem;
    box-shadow: 0 0 20px rgba(168, 85, 247, 0.3);
    margin-bottom: 5px;
}

.points-amount {
    text-align: center;
}

.points-amount .value {
    display: block;
    font-size: 2.8rem;
    font-weight: 900;
    color: #fff;
    line-height: 1;
    margin-bottom: 8px;
    text-shadow: 0 0 15px rgba(255, 255, 255, 0.2);
}

.points-amount .label {
    font-size: 0.75rem;
    color: var(--text-muted);
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 1.5px;
}

.points-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 10px;
}

.btn-point-action {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 12px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.9rem;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-point-action.shop {
    background: #a855f7;
    color: #fff;
    box-shadow: 0 4px 15px rgba(168, 85, 247, 0.3);
}

.btn-point-action.shop:hover {
    background: #9333ea;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(168, 85, 247, 0.4);
}

.btn-point-action.rewards {
    background: rgba(255, 255, 255, 0.05);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.btn-point-action.rewards:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-2px);
}
</style>

<?php
// Namen für Anzeige
$sessionUser = $_SESSION['user'];
$displayDashboardUsername = $sessionUser['username'];
$dashboardAvatarUrl = "https://cdn.discordapp.com/avatars/{$sessionUser['id']}/{$sessionUser['avatar']}.png";
?>

<section class="dashboard-section">
    <div class="dashboard-container">
        <aside class="dashboard-sidebar">
            <div class="user-info-card">
                <img src="<?php echo $dashboardAvatarUrl; ?>" alt="Avatar">
                <div class="user-details">
                    <h3><?php echo htmlspecialchars($displayDashboardUsername); ?></h3>
                    <div class="user-status-badges">
                        <?php if (isset($highestRole) && $highestRole): ?>
                            <span class="status-tag" style="background: rgba(125, 64, 255, 0.1); color: #7d40ff; border: 1px solid rgba(125, 64, 255, 0.2); font-size: 10px; padding: 2px 8px; border-radius: 4px;">
                                <i class="fas fa-circle" style="font-size: 8px; margin-right: 5px;"></i> <?php echo htmlspecialchars($highestRole); ?>
                            </span>
                        <?php endif; ?>
                        <span class="badge-verified">Verifiziert</span>
                    </div>
                </div>
            </div>

            <nav class="dashboard-nav">
                <div class="nav-group">
                    <span class="nav-label">DASHBOARD</span>
                    <a href="?tab=overview" class="<?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'overview') ? 'active' : ''; ?>"><i class="fas fa-th-large"></i> Übersicht</a>
                </div>

                <div class="nav-group">
                    <span class="nav-label">INTEGRATIONEN</span>
                    <a href="?tab=discord" class="<?php echo (isset($_GET['tab']) && $_GET['tab'] == 'discord') ? 'active' : ''; ?>"><i class="fab fa-discord"></i> Discord <span class="tag-beta">Beta</span></a>
                    <?php if ($canModerate): ?>
                        <a href="?tab=moderation" class="<?php echo (isset($_GET['tab']) && $_GET['tab'] == 'moderation') ? 'active' : ''; ?>"><i class="fas fa-shield-alt"></i> Moderation</a>
                    <?php endif; ?>
                </div>

                <div class="nav-group footer-nav">
                    <a href="?tab=settings" class="<?php echo (isset($_GET['tab']) && $_GET['tab'] == 'settings') ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Einstellungen</a>
                    <a href="login.php?action=logout" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </nav>
        </aside>

        <main class="dashboard-main">
            <?php 
            $tab = $_GET['tab'] ?? 'overview';
            
            if ($tab == 'overview'): 
            ?>
                <div class="dashboard-header">
                    <span class="header-breadcrumb"><i class="fas fa-th-large"></i> Dashboard</span>
                    <h1 class="glow-text">Willkommen, <?php echo htmlspecialchars($displayDashboardUsername); ?></h1>
                    <p>Hier siehst du deine Statistiken und Aktivitäten auf der-noxus.de.</p>
                </div>

                <?php if (isset($hasRewardPending) && $hasRewardPending): ?>
                    <div class="reward-reminder-box">
                        <div class="reminder-content">
                            <div class="reminder-icon"><i class="fas fa-gift"></i></div>
                            <div class="reminder-text">
                                <h4>Tägliche Belohnung bereit!</h4>
                                <p>Hole dir jetzt deine Nox-Points ab, um deine Streak zu erhalten.</p>
                            </div>
                        </div>
                        <a href="rewards.php" class="btn-claim-small">Jetzt abholen</a>
                    </div>
                <?php endif; ?>

                <div class="stats-grid">
                    <div class="stat-card user-points-card">
                        <div class="stat-icon"><i class="fas fa-coins"></i></div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo number_format($userPoints, 0, ',', '.'); ?></span>
                            <span class="stat-label">Deine Nox-Points</span>
                        </div>
                    </div>

                    <div class="stat-card highlight">
                        <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo number_format($totalClanPoints, 0, ',', '.'); ?></span>
                            <span class="stat-label">Clan Nox-Points</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo number_format($memberCount, 0, ',', '.'); ?></span>
                            <span class="stat-label">Clan-Mitglieder</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-info">
                            <span class="stat-value">Coming Soon</span>
                            <span class="stat-label">Statistik 3</span>
                        </div>
                    </div>
                </div>

                <div class="content-row">
                    <div class="content-box main-box">
                        <div class="box-header">
                            <h3>Level-Statistik</h3>
                        </div>
                        <div class="level-card-body" style="padding: 20px;">
                            <div class="level-info-top" style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 10px;">
                                <div class="current-level">
                                    <span style="display: block; font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Aktuelles Level</span>
                                    <span style="font-size: 2.5rem; font-weight: 900; color: #a855f7; line-height: 1;">Level <?php echo $currentLevel; ?></span>
                                </div>
                                <div class="xp-info" style="text-align: right;">
                                    <span style="font-weight: 700; color: #fff;"><?php echo $xpDisplay; ?></span>
                                </div>
                            </div>
                            
                            <div class="xp-progress-container" style="height: 12px; background: rgba(255,255,255,0.05); border-radius: 6px; overflow: hidden; margin-bottom: 20px;">
                                <div class="xp-progress-bar" style="width: <?php echo $progressPercent; ?>%; height: 100%; background: linear-gradient(90deg, #a855f7, #6366f1); box-shadow: 0 0 15px rgba(168, 85, 247, 0.5); border-radius: 6px; transition: 1s ease-out;"></div>
                            </div>

                            <div class="next-reward-box" style="background: rgba(168, 85, 247, 0.05); border: 1px dashed rgba(168, 85, 247, 0.3); border-radius: 12px; padding: 15px; display: flex; align-items: center; gap: 15px;">
                                <div style="width: 45px; height: 45px; background: rgba(168, 85, 247, 0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #a855f7; font-size: 1.2rem;">
                                    <i class="fas fa-gift"></i>
                                </div>
                                <div>
                                    <span style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Nächste Belohnung (Level <?php echo $nextLevel['level_number'] ?? ($currentLevel + 1); ?>)</span>
                                    <span style="color: #fff; font-weight: 600;">
                                        <?php 
                                            if ($nextLevel) {
                                                $rewards = [];
                                                if ($nextLevel['reward_points'] > 0) $rewards[] = $nextLevel['reward_points'] . " Nox-Points";
                                                if ($nextLevel['reward_permission']) $rewards[] = "Rang: " . $nextLevel['reward_permission'];
                                                $roles = json_decode($nextLevel['reward_roles_add'] ?? '[]', true);
                                                if (!empty($roles)) $rewards[] = count($roles) . " Discord Rolle(n)";
                                                
                                                echo !empty($rewards) ? implode(", ", $rewards) : "Keine Belohnung";
                                            } else {
                                                echo "Maximale Stufe erreicht!";
                                            }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-box side-box points-display-box">
                        <div class="box-header">
                            <h3>Deine Nox-Points</h3>
                        </div>
                        <div class="points-visual">
                            <div class="points-icon-large">
                                <i class="fas fa-coins"></i>
                            </div>
                            <div class="points-amount">
                                <span class="value"><?php echo number_format($userPoints, 0, ',', '.'); ?></span>
                                <span class="label">Aktuelles Guthaben</span>
                            </div>
                        </div>
                        <div class="points-actions">
                            <a href="shop.php" class="btn-point-action shop">
                                <i class="fas fa-shopping-basket"></i> Zum Shop
                            </a>
                            <a href="rewards.php" class="btn-point-action rewards">
                                <i class="fas fa-gift"></i> Belohnungen
                            </a>
                        </div>
                    </div>
                </div>
            <?php elseif ($tab == 'discord'): ?>
                <div class="dashboard-header">
                    <span class="header-breadcrumb"><i class="fab fa-discord"></i> Discord-Integration</span>
                    <h1 class="glow-text">Discord-Integration <span class="tag-beta">Beta</span></h1>
                    <p>Verwalte deinen Discord-Account, DM-Benachrichtigungen und Bot-Commands.</p>
                </div>

                <div class="discord-integration-grid">
                    <div class="content-box">
                        <div class="box-header">
                            <h3><i class="fas fa-link"></i> Verknüpftes Konto</h3>
                        </div>
                        <div class="linked-account-info">
                            <div class="account-item">
                                <img src="https://cdn.discordapp.com/avatars/<?php echo $_SESSION['user']['id']; ?>/<?php echo $_SESSION['user']['avatar']; ?>.png" alt="Discord Avatar">
                                <div class="account-details">
                                    <span class="account-name"><?php echo htmlspecialchars($_SESSION['user']['username']); ?></span>
                                    <span class="account-meta">ID: <?php echo $_SESSION['user']['id']; ?></span>
                                    <span class="account-meta">Beigetreten: <?php echo $userJoinedAt ? date('d.m.Y', strtotime($userJoinedAt)) : 'Unbekannt'; ?></span>
                                    <span class="account-meta">Aktives Mitglied: <?php echo $isClanMember ? '<i class="fas fa-check-circle" style="color: #4caf50;"></i>' : '<i class="fas fa-times-circle" style="color: #f44336;"></i>'; ?></span>
                                </div>
                                <span class="status-tag success">Verbunden</span>
                            </div>
                        </div>
                    </div>

                    <div class="content-box">
                        <div class="box-header">
                            <h3><i class="fas fa-bell"></i> DM-Benachrichtigungen</h3>
                        </div>
                        <div class="dm-status-list">
                            <div class="status-item">
                                <i class="fas fa-check-circle success"></i>
                                <span>Server-Mitglied</span>
                            </div>
                            <div class="status-item">
                                <i class="fas fa-check-circle success"></i>
                                <span>DM autorisiert</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-box notifications-settings">
                    <div class="box-header">
                        <div class="header-left">
                            <div class="header-icon"><i class="fas fa-bell"></i></div>
                            <div>
                                <h3>Benachrichtigungen</h3>
                                <span class="status-label"><?php echo $loginNotif ? 'Aktiv' : 'Deaktiviert'; ?></span>
                            </div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" <?php echo $loginNotif ? 'checked' : ''; ?> id="toggle-all-notifications">
                            <span class="slider round"></span>
                        </label>
                    </div>

                    <div class="notifications-grid">
                        <div class="notif-item highlight">
                            <div class="notif-icon"><i class="fas fa-sign-in-alt"></i></div>
                            <div class="notif-text">
                                <h4>Anmeldung</h4>
                                <p>Erhalte eine Discord-DM bei jedem Login</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" <?php echo $loginNotif ? 'checked' : ''; ?> class="notif-toggle" data-type="login_notif">
                                <span class="slider round"></span>
                            </label>
                        </div>

                        <div class="notif-item">
                            <div class="notif-icon"><i class="fas fa-shopping-cart"></i></div>
                            <div class="notif-text">
                                <h4>Punkte-Shop Käufe</h4>
                                <p>Erhalte eine Discord-DM bei jedem Kauf im Shop</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" <?php echo $shopBuyNotif ? 'checked' : ''; ?> class="notif-toggle" data-type="shop_buy">
                                <span class="slider round"></span>
                            </label>
                        </div>

                        <div class="notif-item highlight">
                            <div class="notif-icon"><i class="fas fa-layer-group"></i></div>
                            <div class="notif-text">
                                <h4>Level Aufstieg</h4>
                                <p>Erhalte eine Discord-DM bei jedem Level-Up</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" <?php echo $levelUpNotif ? 'checked' : ''; ?> class="notif-toggle" data-type="level_up">
                                <span class="slider round"></span>
                            </label>
                        </div>

                        <?php if ($isAdmin): ?>
                        <div class="notif-item">
                            <div class="notif-icon"><i class="fas fa-ticket-alt"></i></div>
                            <div class="notif-text">
                                <h4>Gutschein Einlösungen</h4>
                                <p>Erhalte eine Discord-DM wenn du einen Code einlöst</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" <?php echo $redeemNotif ? 'checked' : ''; ?> class="notif-toggle" data-type="code_redeem">
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <?php endif; ?>

                        <div class="notif-item highlight">
                            <div class="notif-icon"><i class="fas fa-gift"></i></div>
                            <div class="notif-text">
                                <h4>Giveaway</h4>
                                <p>Erhalte eine Discord-DM beim Start eines Giveaways</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" <?php echo $giveawayNotif ? 'checked' : ''; ?> class="notif-toggle" data-type="giveaway">
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>

                    <div class="notifications-footer">
                        <div id="save-status" class="save-status"></div>
                        <button id="save-notifications" class="btn-minecraft save-btn">
                            <span class="btn-text">Einstellungen speichern</span>
                        </button>
                    </div>
                </div>
            <?php elseif ($tab == 'moderation' && $canModerate): ?>
                <div class="dashboard-header">
                    <span class="header-breadcrumb"><i class="fas fa-shield-alt"></i> Moderation</span>
                    <h1 class="glow-text">Discord Moderation</h1>
                    <p>Führe Moderations-Aktionen direkt über den Discord-Bot aus.</p>
                </div>

                <!-- Permission Info -->
                <div class="permission-info-box">
                    <div class="info-icon"><i class="fas fa-shield-virus"></i></div>
                    <div class="info-text">
                        <strong>Berechtigungs-Level</strong>
                        <p>Warnungen: Mentor+ | Kick/Ban: Nachtwache+</p>
                    </div>
                </div>

                <div class="moderation-grid">
                    <!-- User Search -->
                    <div class="content-box search-box">
                        <div class="box-header">
                            <h3><i class="fas fa-search"></i> User suchen</h3>
                        </div>
                        <div class="search-form-wrapper">
                            <form action="" method="GET" class="mod-search-form">
                                <input type="hidden" name="tab" value="moderation">
                                <div class="search-input-group">
                                    <input type="text" name="search_user" placeholder="Name oder Discord-ID..." value="<?php echo htmlspecialchars($_GET['search_user'] ?? ''); ?>">
                                    <button type="submit" class="btn-claim-small"><i class="fas fa-search"></i></button>
                                </div>
                            </form>

                            <?php 
                            if (!empty($_GET['search_user'])) {
                                $searchTerm = "%" . $_GET['search_user'] . "%";
                                $stmt = $pdo->prepare("SELECT discord_id, username, avatar, roles FROM users WHERE username LIKE ? OR discord_id LIKE ? LIMIT 5");
                                $stmt->execute([$searchTerm, $searchTerm]);
                                $results = $stmt->fetchAll();

                                if ($results) {
                                    echo '<div class="search-results">';
                                    foreach ($results as $res) {
                                        $rolesJson = htmlspecialchars($res['roles'] ?? '[]');
                                        $avatarUrl = $res['avatar'] ? "https://cdn.discordapp.com/avatars/{$res['discord_id']}/{$res['avatar']}.png" : "assets/img/logo_mitte.png";
                                        ?>
                                        <div class="search-result-item">
                                            <div class="user-avatar-wrapper">
                                                <img src="<?php echo $avatarUrl; ?>" alt="Avatar" onerror="this.src='assets/img/logo_mitte.png'">
                                                <div class="status-indicator"></div>
                                            </div>
                                            <div class="user-info-simple">
                                                <span class="username"><?php echo htmlspecialchars($res['username']); ?></span>
                                                <span class="discord-id"><?php echo $res['discord_id']; ?></span>
                                            </div>
                                            <button class="btn-select-user" onclick="selectModUser('<?php echo $res['discord_id']; ?>', '<?php echo htmlspecialchars($res['username'], ENT_QUOTES); ?>', '<?php echo $rolesJson; ?>')">
                                                <i class="fas fa-user-check"></i> Wählen
                                            </button>
                                        </div>
                                        <?php
                                    }
                                    echo '</div>';
                                } else {
                                    echo '<p style="margin-top: 15px; color: var(--text-muted); font-size: 0.8rem; text-align: center;">Keine User gefunden.</p>';
                                }
                            }
                            ?>
                        </div>
                    </div>

                    <div class="content-box actions-box" id="mod-actions-box" style="display: none;">
                        <div class="box-header">
                            <h3><i class="fas fa-gavel"></i> Aktionen für <span id="selected-user-name"></span></h3>
                        </div>
                        <div class="actions-form-wrapper">
                            <input type="hidden" id="selected-user-id">
                            
                            <!-- Tabs for Actions -->
                            <div class="mod-action-tabs">
                                <button onclick="showModAction('warn')" class="btn-tab-mod active" id="tab-btn-warn"><i class="fas fa-exclamation-triangle"></i> Verwarnung</button>
                                <button onclick="<?php echo $canKickBan ? "showModAction('kick')" : "return false;"; ?>" class="btn-tab-mod <?php echo !$canKickBan ? 'disabled' : ''; ?>" id="tab-btn-kick" title="<?php echo !$canKickBan ? 'Keine Berechtigung' : ''; ?>"><i class="fas fa-user-minus"></i> Kick</button>
                                <button onclick="<?php echo $canKickBan ? "showModAction('ban')" : "return false;"; ?>" class="btn-tab-mod <?php echo !$canKickBan ? 'disabled' : ''; ?>" id="tab-btn-ban" title="<?php echo !$canKickBan ? 'Keine Berechtigung' : ''; ?>"><i class="fas fa-gavel"></i> Ban</button>
                            </div>

                            <!-- Warn Section -->
                            <div id="action-warn" class="mod-action-content">
                                <div style="display: grid; gap: 15px; margin-bottom: 20px;">
                                    <div class="input-group" style="margin-bottom: 20px;">
                                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: var(--text-muted);"><i class="fas fa-edit"></i> Grund für die Verwarnung:</label>
                                        <textarea id="warn-reason" placeholder="Grund eingeben (z.B. Beleidigung, Spam...)" style="width: 100%; min-height: 100px; padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.3); color: #fff; resize: vertical; transition: all 0.3s ease;"></textarea>
                                    </div>
                                </div>
                                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 15px;">Wähle die Verwarnungsstufe aus:</p>
                                <div style="display: grid; gap: 10px;">
                                    <button id="btn-warn-1" onclick="executeModAction('warn_1')" class="btn-mod-action warn">
                                        <div class="action-info">
                                            <strong>Warnung 1</strong>
                                            <span>Sanktion: 50.000$ Clan-Bank</span>
                                        </div>
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                    <button id="btn-warn-2" onclick="executeModAction('warn_2')" class="btn-mod-action warn">
                                        <div class="action-info">
                                            <strong>Warnung 2</strong>
                                            <span>Sanktion: 250.000$ Clan-Bank</span>
                                        </div>
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                    <button id="btn-warn-3" onclick="executeModAction('warn_3')" class="btn-mod-action warn-3">
                                        <div class="action-info">
                                            <strong>Warnung 3</strong>
                                            <span>Sanktion: Clan-Rauswurf</span>
                                        </div>
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Kick Section -->
                            <div id="action-kick" class="mod-action-content" style="display: none;">
                                <div style="display: grid; gap: 20px;">
                                    <div class="input-group">
                                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: var(--text-muted);"><i class="fas fa-edit"></i> Grund für den Kick:</label>
                                        <textarea id="kick-reason" placeholder="Triftigen Grund eingeben..." style="width: 100%; min-height: 100px; padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.3); color: #fff; resize: vertical; transition: all 0.3s ease;"></textarea>
                                    </div>
                                    <button onclick="<?php echo $canKickBan ? "executeModAction('kick')" : "return false;"; ?>" class="btn-mod-action danger <?php echo !$canKickBan ? 'disabled' : ''; ?>" <?php echo !$canKickBan ? 'disabled' : ''; ?>>
                                        <div class="action-info">
                                            <strong>Discord Kick</strong>
                                            <span>User wird vom Server gekickt</span>
                                        </div>
                                        <i class="fas fa-sign-out-alt"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Ban Section -->
                            <div id="action-ban" class="mod-action-content" style="display: none;">
                                <div style="display: grid; gap: 20px;">
                                    <div class="input-group">
                                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: var(--text-muted);"><i class="fas fa-edit"></i> Grund für den Ban:</label>
                                        <textarea id="ban-reason" placeholder="Triftigen Grund eingeben..." style="width: 100%; min-height: 100px; padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.3); color: #fff; resize: vertical; transition: all 0.3s ease;"></textarea>
                                    </div>
                                    <button onclick="<?php echo $canKickBan ? "executeModAction('ban')" : "return false;"; ?>" class="btn-mod-action danger <?php echo !$canKickBan ? 'disabled' : ''; ?>" <?php echo !$canKickBan ? 'disabled' : ''; ?>>
                                        <div class="action-info">
                                            <strong>Discord Ban</strong>
                                            <span>User wird vom Server gebannt</span>
                                        </div>
                                        <i class="fas fa-gavel"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <style>
                .search-results {
                    margin-top: 20px;
                    display: grid;
                    gap: 12px;
                }
                
                .search-result-item {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 12px 16px;
                    background: rgba(255, 255, 255, 0.03);
                    border: 1px solid rgba(255, 255, 255, 0.05);
                    border-radius: 12px;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    animation: slideInUp 0.4s ease backwards;
                }
                
                .search-result-item:hover {
                    background: rgba(168, 85, 247, 0.08);
                    border-color: rgba(168, 85, 247, 0.3);
                    transform: translateX(5px);
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
                }

                .user-avatar-wrapper {
                    position: relative;
                    width: 40px;
                    height: 40px;
                }

                .user-avatar-wrapper img {
                    width: 100%;
                    height: 100%;
                    border-radius: 50%;
                    object-fit: cover;
                    border: 2px solid rgba(255, 255, 255, 0.1);
                }

                .status-indicator {
                    position: absolute;
                    bottom: 0;
                    right: 0;
                    width: 12px;
                    height: 12px;
                    background: #10b981;
                    border: 2px solid #050510;
                    border-radius: 50%;
                }

                .user-info-simple {
                    flex: 1;
                    margin-left: 15px;
                    display: flex;
                    flex-direction: column;
                }

                .user-info-simple .username {
                    font-weight: 700;
                    font-size: 0.95rem;
                    color: #fff;
                }

                .user-info-simple .discord-id {
                    font-size: 0.75rem;
                    color: var(--text-muted);
                }

                .btn-select-user {
                    background: rgba(168, 85, 247, 0.2);
                    color: #a855f7;
                    border: 1px solid rgba(168, 85, 247, 0.3);
                    padding: 6px 14px;
                    border-radius: 8px;
                    font-weight: 600;
                    font-size: 0.8rem;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .btn-select-user:hover {
                    background: #a855f7;
                    color: #fff;
                    transform: scale(1.05);
                    box-shadow: 0 0 15px rgba(168, 85, 247, 0.4);
                }

                @keyframes slideInUp {
                    from { opacity: 0; transform: translateY(20px); }
                    to { opacity: 1; transform: translateY(0); }
                }

                .search-result-item:nth-child(1) { animation-delay: 0.1s; }
                .search-result-item:nth-child(2) { animation-delay: 0.15s; }
                .search-result-item:nth-child(3) { animation-delay: 0.2s; }
                .search-result-item:nth-child(4) { animation-delay: 0.25s; }
                .search-result-item:nth-child(5) { animation-delay: 0.3s; }
                
                .permission-info-box {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    padding: 15px 20px;
                    background: rgba(168, 85, 247, 0.05);
                    border: 1px solid rgba(168, 85, 247, 0.2);
                    border-radius: 12px;
                    margin-bottom: 25px;
                    animation: fadeIn 0.5s ease;
                }

                .permission-info-box .info-icon {
                    font-size: 1.4rem;
                    color: #a855f7;
                    filter: drop-shadow(0 0 5px rgba(168, 85, 247, 0.5));
                }

                .permission-info-box .info-text strong {
                    display: block;
                    font-size: 0.9rem;
                    color: #fff;
                    margin-bottom: 2px;
                }

                .permission-info-box .info-text p {
                    font-size: 0.8rem;
                    color: var(--text-muted);
                    margin: 0;
                }

                .search-form-wrapper {
                    padding: 20px;
                }

                .search-input-group {
                    display: flex;
                    gap: 10px;
                }

                .search-input-group input {
                    flex: 1;
                    padding: 12px 15px;
                    border-radius: 10px;
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    background: rgba(0, 0, 0, 0.3);
                    color: #fff;
                    font-size: 0.9rem;
                    transition: all 0.3s ease;
                }

                .search-input-group input:focus {
                    border-color: #a855f7;
                    background: rgba(0, 0, 0, 0.4);
                    outline: none;
                    box-shadow: 0 0 15px rgba(168, 85, 247, 0.2);
                }

                .search-input-group .btn-claim-small {
                    padding: 10px 20px;
                    height: auto;
                }

                .actions-form-wrapper {
                    padding: 20px;
                }

                .moderation-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 20px;
                    animation: fadeIn 0.5s ease;
                }
                
                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                
                .content-box {
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                }
                
                .mod-action-tabs {
                    display: flex;
                    gap: 5px;
                    margin-bottom: 25px;
                    background: rgba(0, 0, 0, 0.2);
                    padding: 5px;
                    border-radius: 12px;
                    border: 1px solid rgba(255, 255, 255, 0.05);
                }
                
                .btn-tab-mod {
                    flex: 1;
                    padding: 12px;
                    background: transparent;
                    border: none;
                    border-radius: 8px;
                    color: var(--text-muted);
                    cursor: pointer;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    font-size: 0.85rem;
                    font-weight: 700;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                }

                .btn-tab-mod:hover:not(.active) {
                    background: rgba(255, 255, 255, 0.05);
                    color: #fff;
                }

                .btn-tab-mod.active {
                    background: #a855f7;
                    color: #fff;
                    box-shadow: 0 4px 15px rgba(168, 85, 247, 0.3);
                }

                .btn-tab-mod.disabled {
                    opacity: 0.3;
                    filter: grayscale(1);
                    cursor: not-allowed !important;
                }
                
                .btn-mod-action {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 16px 20px;
                    background: rgba(255, 255, 255, 0.03);
                    border: 1px solid rgba(255, 255, 255, 0.05);
                    border-radius: 14px;
                    color: #fff;
                    cursor: pointer;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    text-align: left;
                    width: 100%;
                    position: relative;
                    overflow: hidden;
                }

                .btn-mod-action::before {
                    content: "";
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 4px;
                    height: 100%;
                    background: transparent;
                    transition: all 0.3s ease;
                }

                .btn-mod-action:hover:not(:disabled) {
                    background: rgba(255, 255, 255, 0.06);
                    transform: translateX(10px);
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
                }

                .btn-mod-action.disabled {
                    opacity: 0.3;
                    filter: grayscale(1);
                    cursor: not-allowed !important;
                    transform: none !important;
                    box-shadow: none !important;
                }

                .btn-mod-action.warn:hover:not(:disabled) { 
                    border-color: rgba(234, 179, 8, 0.5); 
                }
                .btn-mod-action.warn::before { background: #eab308; }

                .btn-mod-action.warn-3:hover:not(:disabled) { 
                    border-color: rgba(249, 115, 22, 0.5); 
                }
                .btn-mod-action.warn-3::before { background: #f97316; }

                .btn-mod-action.danger:hover:not(:disabled) { 
                    border-color: rgba(239, 68, 68, 0.5); 
                }
                .btn-mod-action.danger::before { background: #ef4444; }
                
                .btn-mod-action:disabled {
                    opacity: 0.3;
                    cursor: not-allowed;
                    filter: grayscale(1);
                }
                
                .search-result-item {
                    transition: all 0.3s ease;
                }
                .search-result-item:hover {
                    background: rgba(168, 85, 247, 0.1) !important;
                    border-color: rgba(168, 85, 247, 0.3) !important;
                    transform: translateY(-2px);
                }
                
                .recent-action-item {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 14px 18px;
                    background: rgba(255, 255, 255, 0.02);
                    border-radius: 12px;
                    border: 1px solid rgba(255, 255, 255, 0.05);
                    margin-bottom: 10px;
                    animation: slideInRight 0.4s ease backwards;
                    transition: all 0.3s ease;
                }
                
                .recent-action-item:hover {
                    background: rgba(255, 255, 255, 0.04);
                    border-color: rgba(168, 85, 247, 0.2);
                    transform: scale(1.01);
                }
                
                .recent-action-item .action-details {
                    display: flex;
                    flex-direction: column;
                    gap: 4px;
                }

                .recent-action-item .action-target {
                    font-weight: 700;
                    color: #fff;
                    font-size: 0.95rem;
                }

                .recent-action-item .action-meta {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    font-size: 0.8rem;
                    color: var(--text-muted);
                }

                .recent-action-item .action-type {
                    color: #a855f7;
                    font-weight: 600;
                }
                
                .glow-text {
                    text-shadow: 0 0 15px rgba(168, 85, 247, 0.5);
                }
                
                .recent-action-item.undone {
                    opacity: 0.5;
                    filter: grayscale(1);
                    pointer-events: none;
                    border-color: rgba(255,255,255,0.02);
                }
                
                .btn-undo:disabled {
                    background: rgba(255,255,255,0.05);
                    color: var(--text-muted);
                    border-color: transparent;
                    cursor: not-allowed;
                }

                #mod-actions-box {
                    animation: slideInRight 0.5s cubic-bezier(0.4, 0, 0.2, 1);
                }

                @keyframes slideInRight {
                    from { opacity: 0; transform: translateX(30px); }
                    to { opacity: 1; transform: translateX(0); }
                }
                
                .btn-undo {
                    background: rgba(239, 68, 68, 0.15);
                    color: #ef4444;
                    border: 1px solid rgba(239, 68, 68, 0.3);
                    padding: 5px 12px;
                    border-radius: 6px;
                    font-size: 0.75rem;
                    cursor: pointer;
                    transition: all 0.2s;
                }
                .btn-undo:hover {
                    background: #ef4444;
                    color: #fff;
                }

                .mod-action-content {
                    animation: fadeIn 0.3s ease;
                }
                
                .btn-mod-action .action-info strong { display: block; font-size: 1rem; margin-bottom: 2px; }
                .btn-mod-action .action-info span { font-size: 0.75rem; color: var(--text-muted); }
                .btn-mod-action i { color: var(--text-muted); font-size: 0.9rem; }

                @media (max-width: 992px) {
                    .moderation-grid { grid-template-columns: 1fr; }
                }
                </style>

                <!-- Recent Actions / Undo Section -->
                <div class="content-box undo-box" style="margin-top: 20px;">
                    <div class="box-header">
                        <h3><i class="fas fa-undo"></i> Deine letzten Aktionen</h3>
                    </div>
                    <div style="padding: 20px;">
                        <?php
                        // Fetch last 3 actions within 30 minutes
                        $stmtRecent = $pdo->prepare("
                            SELECT ml.*, u.username as target_name 
                            FROM moderation_logs ml
                            LEFT JOIN users u ON ml.user_id = u.discord_id
                            WHERE ml.admin_id = ? 
                            AND ml.created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
                            ORDER BY ml.created_at DESC 
                            LIMIT 3
                        ");
                        $stmtRecent->execute([$_SESSION['user']['id']]);
                        $recentActions = $stmtRecent->fetchAll();

                        if ($recentActions):
                            foreach ($recentActions as $ra):
                                $actionNameMap = [
                                    'warn_1' => 'Warnung 1',
                                    'warn_2' => 'Warnung 2',
                                    'warn_3' => 'Warnung 3',
                                    'kick' => 'Kick',
                                    'ban' => 'Ban'
                                ];
                                $actionName = $actionNameMap[$ra['action']] ?? $ra['action'];
                                $isUndone = (bool)($ra['is_undone'] ?? false);
                        ?>
                            <div class="recent-action-item <?php echo $isUndone ? 'undone' : ''; ?>">
                                <div class="action-details">
                                    <span class="action-target"><?php echo htmlspecialchars($ra['target_name'] ?? $ra['user_id']); ?></span>
                                    <div class="action-meta">
                                        <span>Aktion: <span class="action-type"><?php echo $actionName; ?></span></span>
                                        <?php if ($isUndone): ?>
                                            <span class="badge" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); padding: 2px 6px; border-radius: 4px; font-size: 0.7rem;">RÜCKGÄNGIG GEMACHT</span>
                                        <?php endif; ?>
                                    </div>
                                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px; opacity: 0.8;"><?php echo htmlspecialchars($ra['details']); ?></p>
                                </div>
                                <button onclick="undoModAction(<?php echo $ra['id']; ?>, '<?php echo $ra['action']; ?>')" class="btn-undo" <?php echo $isUndone ? 'disabled' : ''; ?>>
                                    <i class="fas fa-history"></i> <?php echo $isUndone ? 'Erledigt' : 'Rückgängig'; ?>
                                </button>
                            </div>
                        <?php endforeach; else: ?>
                            <p style="text-align: center; color: var(--text-muted); font-size: 0.85rem; padding: 10px;">Keine rückgängig machbaren Aktionen in den letzten 30 Min.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- History Modal -->
                <div id="historyModal" class="modal">
                    <div class="modal-content" style="max-width: 700px; background: #0a0a1a; border: 1px solid rgba(168, 85, 247, 0.2);">
                        <div class="modal-header">
                            <h3><i class="fas fa-history"></i> Moderations-Historie</h3>
                            <span class="close" onclick="closeModal('historyModal')">&times;</span>
                        </div>
                        <div id="historyModalBody" style="max-height: 500px; overflow-y: auto; padding: 10px;">
                            <!-- Dynamically filled -->
                        </div>
                    </div>
                </div>

                <style>
                .modal {
                    display: none;
                    position: fixed;
                    z-index: 10000;
                    left: 0;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    overflow: auto;
                    background-color: rgba(0,0,0,0.8);
                    backdrop-filter: blur(10px);
                    animation: fadeIn 0.3s ease;
                }
                .modal-content {
                    background: #0a0a1a;
                    margin: 10% auto;
                    padding: 0;
                    border: 1px solid rgba(168, 85, 247, 0.2);
                    width: 90%;
                    max-width: 700px;
                    border-radius: 16px;
                    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
                    overflow: hidden;
                }
                .modal-header {
                    padding: 20px 25px;
                    background: rgba(168, 85, 247, 0.05);
                    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .modal-header h3 { margin: 0; font-size: 1.2rem; display: flex; align-items: center; gap: 12px; color: #fff; }
                .modal-header .close { color: #fff; opacity: 0.5; font-size: 1.5rem; cursor: pointer; transition: 0.2s; }
                .modal-header .close:hover { opacity: 1; }

                .history-item {
                    background: rgba(255, 255, 255, 0.03);
                    border: 1px solid rgba(255, 255, 255, 0.05);
                    border-radius: 12px;
                    padding: 15px;
                    margin-bottom: 12px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    transition: all 0.2s;
                }
                .history-item:hover {
                    background: rgba(168, 85, 247, 0.05);
                    border-color: rgba(168, 85, 247, 0.2);
                }
                .history-item.undone {
                    opacity: 0.6;
                    background: rgba(239, 68, 68, 0.02);
                }
                .history-details { flex: 1; }
                .history-action-name { font-weight: 700; color: #fff; font-size: 1rem; display: block; margin-bottom: 2px; }
                .history-meta { font-size: 0.8rem; color: var(--text-muted); display: flex; gap: 12px; margin-bottom: 5px; }
                .history-reason { font-size: 0.85rem; color: rgba(255,255,255,0.8); background: rgba(0,0,0,0.2); padding: 5px 10px; border-radius: 4px; display: inline-block; }
                .badge-undone { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; margin-left: 8px; }
                
                #historyModal .close { color: #fff; opacity: 0.5; transition: 0.2s; }
                #historyModal .close:hover { opacity: 1; }

                .btn-history-small {
                    background: rgba(0, 123, 255, 0.1);
                    color: #007bff;
                    border: 1px solid rgba(0, 123, 255, 0.2);
                    padding: 6px 12px;
                    border-radius: 8px;
                    font-weight: 600;
                    font-size: 0.8rem;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                }
                .btn-history-small:hover {
                    background: #007bff;
                    color: #fff;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
                }
                </style>

                <script>
                const canUndoGlobal = <?php 
                    $canUndo = $isAdmin;
                    $isNachtwache = false;
                    if (isset($userRoles) && is_array($userRoles)) {
                        if (in_array('1504743897731956806', $userRoles)) $isNachtwache = true;
                        // Mentor Check
                        if (in_array('1504744004686975107', $userRoles) && !$isAdmin && !$isNachtwache) {
                            $canUndo = false;
                        } else if ($isAdmin || $isNachtwache) {
                            $canUndo = true;
                        }
                    }
                    echo json_encode($canUndo); 
                ?>;

                function openHistoryModal(userId, username) {
                    const body = document.getElementById('historyModalBody');
                    body.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Lade Historie...</p></div>';
                    document.getElementById('historyModal').style.display = 'block';
                    document.body.classList.add('modal-open');

                    fetch(`api/admin_actions.php?action=get_user_history&target_id=${userId}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                if (data.history.length === 0) {
                                    body.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 20px;">Keine Moderations-Einträge gefunden.</p>';
                                    return;
                                }

                                const actionMap = {
                                    'warn_1': 'Warnung 1',
                                    'warn_2': 'Warnung 2',
                                    'warn_3': 'Warnung 3',
                                    'kick': 'Kick',
                                    'ban': 'Ban'
                                };

                                let html = '';
                                data.history.forEach(item => {
                                    const isUndone = parseInt(item.is_undone) === 1;
                                    const date = new Date(item.created_at).toLocaleString('de-DE');
                                    
                                    html += `
                                        <div class="history-item ${isUndone ? 'undone' : ''}">
                                            <div class="history-details">
                                                <span class="history-action-name">
                                                    ${actionMap[item.action] || item.action}
                                                    ${isUndone ? '<span class="badge-undone">RÜCKGÄNGIG GEMACHT</span>' : ''}
                                                </span>
                                                <div class="history-meta">
                                                    <span><i class="fas fa-user-shield"></i> ${item.admin_name || 'System'}</span>
                                                    <span><i class="fas fa-calendar-alt"></i> ${date}</span>
                                                </div>
                                                <div class="history-reason">
                                                    ${item.details || 'Kein Grund angegeben'}
                                                </div>
                                            </div>
                                            ${(!isUndone && canUndoGlobal && item.action !== 'kick') ? `
                                                <button class="btn-undo" onclick="undoModAction(${item.id}, '${item.action}')">
                                                    <i class="fas fa-history"></i> Rückgängig
                                                </button>
                                            ` : ''}
                                        </div>
                                    `;
                                });
                                body.innerHTML = html;
                            } else {
                                body.innerHTML = `<p style="text-align: center; color: #ef4444; padding: 20px;">Fehler: ${data.error}</p>`;
                            }
                        });
                }

                function closeModal(id) {
                    document.getElementById(id).style.display = 'none';
                    document.body.classList.remove('modal-open');
                }

                function selectModUser(id, name, rolesJson) {
                    const roles = JSON.parse(rolesJson || '[]');
                    const roleWarn1 = '1508935607840149725';
                    const roleWarn2 = '1508935611472548102';
                    const roleWarn3 = '1508935613947318322';

                    const box = document.getElementById('mod-actions-box');
                    box.style.display = 'none'; // Reset animation
                    
                    setTimeout(() => {
                        document.getElementById('selected-user-id').value = id;
                        document.getElementById('selected-user-name').innerText = name;
                        
                        // Reset and handle warning buttons
                        const btn1 = document.getElementById('btn-warn-1');
                        const btn2 = document.getElementById('btn-warn-2');
                        const btn3 = document.getElementById('btn-warn-3');

                        btn1.disabled = roles.includes(roleWarn1) || roles.includes(roleWarn2) || roles.includes(roleWarn3);
                        btn2.disabled = !roles.includes(roleWarn1) || roles.includes(roleWarn2) || roles.includes(roleWarn3);
                        btn3.disabled = !roles.includes(roleWarn2) || roles.includes(roleWarn3);

                        box.style.display = 'block';
                        box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }, 50);
                }

                function showModAction(action) {
                    document.querySelectorAll('.mod-action-content').forEach(el => el.style.display = 'none');
                    document.querySelectorAll('.btn-tab-mod').forEach(el => el.classList.remove('active'));
                    
                    document.getElementById('action-' + action).style.display = 'block';
                    document.getElementById('tab-btn-' + action).classList.add('active');
                }

                function executeModAction(type) {
                    const userId = document.getElementById('selected-user-id').value;
                    const userName = document.getElementById('selected-user-name').innerText;
                    let reason = '';
                    
                    if (type.startsWith('warn_')) reason = document.getElementById('warn-reason').value;
                    if (type === 'kick') reason = document.getElementById('kick-reason').value;
                    if (type === 'ban') reason = document.getElementById('ban-reason').value;

                    if (!reason.trim()) {
                        alert('Bitte gib einen triftigen Grund an!');
                        return;
                    }

                    if (!confirm('Möchtest du diese Aktion wirklich ausführen?')) return;

                    const formData = new FormData();
                    formData.append('action', 'mod_action');
                    formData.append('type', type);
                    formData.append('target_id', userId);
                    formData.append('target_name', userName);
                    formData.append('reason', reason);

                    fetch('api/admin_actions.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Aktion erfolgreich ausgeführt!');
                            // Reset inputs
                            document.getElementById('warn-reason').value = '';
                            document.getElementById('kick-reason').value = '';
                            document.getElementById('ban-reason').value = '';
                            
                            if (type === 'kick' || type === 'ban') {
                                location.reload();
                            }
                        } else {
                            alert('Fehler: ' + data.error);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('Ein technischer Fehler ist aufgetreten.');
                    });
                }
                </script>
            <?php elseif ($tab == 'settings'): ?>
                <div class="dashboard-header">
                    <span class="header-breadcrumb"><i class="fas fa-cog"></i> Einstellungen</span>
                    <h1 class="glow-text">Kontoeinstellungen</h1>
                    <p>Passe dein Erlebnis auf der-noxus.de an deine Wünsche an.</p>
                </div>
                
                <div class="content-box notifications-settings">
                    <div class="box-header">
                        <div class="header-left">
                            <div class="header-icon"><i class="fas fa-volume-up"></i></div>
                            <div>
                                <h3>Sound & Audio</h3>
                                <span class="status-label">Konfiguriere deine Audio-Umgebung</span>
                            </div>
                        </div>
                    </div>

                    <div class="notifications-grid">
                        <div class="notif-item highlight">
                            <div class="notif-icon"><i class="fas fa-mouse-pointer"></i></div>
                            <div class="notif-text">
                                <h4>UI Sound-Effekte</h4>
                                <p>Sounds beim Klicken, Hovern und anderen Interaktionen</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" id="ui_sounds_toggle" <?php echo ($uiSoundsActive ? 'checked' : ''); ?> class="setting-toggle" data-type="ui_sounds">
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>

                    <div class="notifications-footer">
                        <div id="save-status-settings" class="save-status"></div>
                        <button id="save-settings" class="btn-minecraft save-btn">
                            <span class="btn-text">Einstellungen speichern</span>
                        </button>
                    </div>
                </div>

                <div class="content-box" style="margin-top: 20px;">
                    <div class="box-header">
                        <div class="header-left">
                            <div class="header-icon"><i class="fas fa-user-circle"></i></div>
                            <div>
                                <h3>Profil & Account</h3>
                                <span class="status-label">Verwalte dein Profil</span>
                            </div>
                        </div>
                    </div>
                    <div style="padding: 20px;">
                        <p style="color: var(--text-muted); font-size: 0.85rem;">Weitere Profil-Einstellungen (Avatar-Rahmen, Banner etc.) folgen in Kürze.</p>
                    </div>
                </div>

                <script>
                document.getElementById('save-settings').addEventListener('click', function() {
                    saveNoxSettings('save-settings', 'save-status-settings');
                });
                </script>
            <?php endif; ?>
        </main>
    </div>
</section>

<?php include 'includes/footer.php'; ?>