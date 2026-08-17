<?php
include 'includes/header.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !($webPermission === 'Administrator')) {
    header('Location: index.php');
    exit;
}

// Ensure database table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS levels (
        level_number INT PRIMARY KEY,
        xp_required INT NOT NULL,
        reward_points INT DEFAULT 0,
        reward_roles_add TEXT DEFAULT NULL,
        reward_roles_remove TEXT DEFAULT NULL,
        reward_permission VARCHAR(50) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Ensure user columns exist
    try { $pdo->exec("ALTER TABLE users ADD COLUMN xp FLOAT DEFAULT 0"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE users ADD COLUMN level INT DEFAULT 0"); } catch (Exception $e) {}

    // Ensure settings table exists for level system
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS level_settings (
            setting_key VARCHAR(50) PRIMARY KEY,
            setting_value VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        
        // Default values
        $pdo->exec("INSERT IGNORE INTO level_settings (setting_key, setting_value) VALUES 
            ('min_msg_length', '5'),
            ('msg_cooldown', '60'),
            ('min_voice_time', '1'),
            ('ignored_channels', '')");
    } catch (Exception $e) {}
} catch (Exception $e) {}

// Fetch settings
$stmtSettings = $pdo->query("SELECT * FROM level_settings");
$lvlSettings = [];
while($row = $stmtSettings->fetch()) {
    $lvlSettings[$row['setting_key']] = $row['setting_value'];
}
$minMsgLength = $lvlSettings['min_msg_length'] ?? 5;
$msgCooldown = $lvlSettings['msg_cooldown'] ?? 60;
$minVoiceTime = $lvlSettings['min_voice_time'] ?? 1;
$ignoredChannels = $lvlSettings['ignored_channels'] ?? '';

// Fetch all levels
$stmt = $pdo->query("SELECT * FROM levels ORDER BY level_number ASC");
$levels = $stmt->fetchAll();
?>

<style>
.admin-levels-grid {
    display: grid;
    gap: 30px;
}

.level-form-container {
    background: rgba(255, 255, 255, 0.02);
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--text-muted);
    font-size: 0.9rem;
}

.form-control {
    width: 100%;
    background: rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(168, 85, 247, 0.2);
    border-radius: 8px;
    padding: 12px;
    color: white;
    outline: none;
    transition: 0.3s;
}

.form-control:focus {
    border-color: #a855f7;
    background: rgba(168, 85, 247, 0.05);
    box-shadow: 0 0 15px rgba(168, 85, 247, 0.1);
}

.reward-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: rgba(168, 85, 247, 0.1);
    border: 1px solid rgba(168, 85, 247, 0.2);
    border-radius: 6px;
    font-size: 0.8rem;
    color: #d8b4fe;
    margin-right: 5px;
    margin-bottom: 5px;
}

.admin-table th {
    padding: 15px 20px;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 1px;
    color: var(--text-muted);
    border-bottom: 2px solid rgba(255, 255, 255, 0.05);
}

.admin-table td {
    padding: 20px;
    vertical-align: middle;
}

.level-badge {
    background: linear-gradient(135deg, #a855f7, #6366f1);
    color: white;
    padding: 5px 12px;
    border-radius: 6px;
    font-weight: 800;
    box-shadow: 0 4px 10px rgba(168, 85, 247, 0.3);
}

.xp-required-text {
    font-family: 'JetBrains Mono', monospace;
    color: #fff;
    font-weight: 600;
}
</style>

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
                    <?php 
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
                    ?>
                    <?php if ($isAdmin): ?>
                        <a href="admin_logs.php?category=Giveaways"><i class="fas fa-gift"></i> Giveaways</a>
                        <a href="admin_logs.php?category=Members"><i class="fas fa-users-cog"></i> Mitglieder</a>
                        <a href="admin_logs.php?category=Levels"><i class="fas fa-chart-line"></i> Level</a>
                        <a href="admin_logs.php?category=Points"><i class="fas fa-coins"></i> Nox-Points</a>
                    <?php endif; ?>
                    <?php if ($isModerator): ?>
                        <a href="admin_logs.php?category=Moderation"><i class="fas fa-shield-alt"></i> Moderation</a>
                    <?php endif; ?>
                </div>
                
                <div class="nav-group footer-nav">
                    <a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
                    <a href="login.php?action=logout" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </nav>
        </aside>

        <main class="dashboard-main">
            <div class="dashboard-header">
                <span class="header-breadcrumb"><i class="fas fa-user-shield"></i> Administration</span>
                <h1 class="glow-text">Levelsystem</h1>
                <p>Erstelle und verwalte die Level, XP-Anforderungen und Belohnungen.</p>
            </div>

            <div class="content-box">
                <div class="box-header">
                    <h3><i class="fas fa-shield-halved"></i> Anti-Spam & XP Einstellungen</h3>
                </div>
                <form id="settingsForm" style="padding: 20px;">
                    <input type="hidden" name="action" value="save_settings">
                    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Mindestlänge (Nachricht)</label>
                            <input type="number" name="min_msg_length" class="form-control" value="<?php echo $minMsgLength; ?>" min="1" title="Mindestzeichen für XP">
                        </div>
                        <div class="form-group">
                            <label>Cooldown (Sekunden)</label>
                            <input type="number" name="msg_cooldown" class="form-control" value="<?php echo $msgCooldown; ?>" min="0" title="Cooldown zwischen gezählten Nachrichten">
                        </div>
                        <div class="form-group">
                            <label>Mindestzeit Talk (Minuten)</label>
                            <input type="number" name="min_voice_time" class="form-control" value="<?php echo $minVoiceTime; ?>" min="1" title="Wie lange man mind. im Talk sein muss für XP">
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 20px;">
                        <label>Gesperrte Kanäle (IDs, Komma-getrennt)</label>
                        <input type="text" name="ignored_channels" class="form-control" value="<?php echo htmlspecialchars($ignoredChannels); ?>" placeholder="ID1, ID2, ID3...">
                    </div>
                    <button type="submit" class="btn-primary" style="margin-top: 20px; padding: 10px 25px;">Einstellungen speichern</button>
                </form>
            </div>

            <div class="content-box" style="margin-top: 30px;">
                <div class="box-header">
                    <h3><i class="fas fa-plus-circle"></i> Neues Level erstellen</h3>
                </div>
                <form id="levelForm" style="padding: 20px;">
                    <input type="hidden" name="action" value="save_level">
                    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Level (1-n)</label>
                            <input type="number" name="level_number" class="form-control" required min="1">
                        </div>
                        <div class="form-group">
                            <label>Benötigte XP</label>
                            <input type="number" name="xp_required" class="form-control" required min="1">
                        </div>
                        <div class="form-group">
                            <label>Belohnung: Punkte (optional)</label>
                            <input type="number" name="reward_points" class="form-control" value="0">
                        </div>
                        <div class="form-group">
                            <label>Belohnung: Web-Permission (optional)</label>
                            <select name="reward_permission" class="form-control">
                                <option value="">Keine</option>
                                <option value="User">User</option>
                                <option value="GW-Manager">GW-Manager</option>
                                <option value="Administrator">Administrator</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Rollen geben (IDs, Komma-getrennt)</label>
                            <input type="text" name="reward_roles_add" class="form-control" placeholder="ID1, ID2...">
                        </div>
                        <div class="form-group">
                            <label>Rollen entfernen (IDs, Komma-getrennt)</label>
                            <input type="text" name="reward_roles_remove" class="form-control" placeholder="ID1, ID2...">
                        </div>
                    </div>
                    <button type="submit" class="btn-primary" style="margin-top: 20px; padding: 10px 25px;">Level speichern</button>
                </form>
            </div>

            <div class="content-box" style="margin-top: 30px;">
                <div class="box-header">
                    <h3><i class="fas fa-list"></i> Bestehende Level</h3>
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Level</th>
                                <th>XP Benötigt</th>
                                <th>Belohnungen</th>
                                <th class="text-right">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($levels)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">Noch keine Level erstellt.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($levels as $l): ?>
                                    <tr>
                                        <td><span class="level-badge">Level <?php echo $l['level_number']; ?></span></td>
                                        <td><span class="xp-required-text"><?php echo number_format($l['xp_required'], 0, ',', '.'); ?> XP</span></td>
                                        <td>
                                            <div class="reward-list">
                                                <?php if ($l['reward_points'] > 0): ?>
                                                    <span class="reward-tag"><i class="fas fa-coins"></i> +<?php echo $l['reward_points']; ?> Punkte</span>
                                                <?php endif; ?>
                                                <?php if ($l['reward_permission']): ?>
                                                    <span class="reward-tag"><i class="fas fa-user-shield"></i> Permission: <?php echo $l['reward_permission']; ?></span>
                                                <?php endif; ?>
                                                <?php 
                                                    $rolesAdd = json_decode($l['reward_roles_add'] ?? '[]', true);
                                                    if (!empty($rolesAdd)):
                                                ?>
                                                    <span class="reward-tag"><i class="fas fa-plus-circle"></i> +<?php echo count($rolesAdd); ?> Rollen</span>
                                                <?php endif; ?>
                                                <?php 
                                                    $rolesRemove = json_decode($l['reward_roles_remove'] ?? '[]', true);
                                                    if (!empty($rolesRemove)):
                                                ?>
                                                    <span class="reward-tag" style="background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.2); color: #fca5a5;"><i class="fas fa-minus-circle"></i> -<?php echo count($rolesRemove); ?> Rollen</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            <button class="btn-action btn-remove" onclick="deleteLevel(<?php echo $l['level_number']; ?>)" title="Level löschen">
                                                <i class="fas fa-trash"></i>
                                            </button>
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

<script>
document.getElementById('levelForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('api/admin_levels_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showNotification('Level erfolgreich gespeichert!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification('Fehler: ' + data.message, 'error');
        }
    });
};

document.getElementById('settingsForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('api/admin_levels_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showNotification('Einstellungen gespeichert!', 'success');
        } else {
            showNotification('Fehler: ' + data.message, 'error');
        }
    });
};

function deleteLevel(level) {
    if (confirm('Bist du sicher, dass du Level ' + level + ' löschen möchtest?')) {
        const formData = new FormData();
        formData.append('action', 'delete_level');
        formData.append('level_number', level);
        
        fetch('api/admin_levels_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showNotification('Level gelöscht!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Fehler: ' + data.message, 'error');
            }
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>
