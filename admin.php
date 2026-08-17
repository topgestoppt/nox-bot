<?php
ob_start();
require_once 'includes/config.php';
session_start();

// Handle Maintenance Mode Toggle (BEFORE any output)
if (isset($_POST['action']) && $_POST['action'] == 'toggle_maintenance') {
    try {
        $newState = (int)$_POST['maintenance_state'];
        $stmt = $pdo->prepare("INSERT INTO clan_stats (stat_key, stat_value) VALUES ('maintenance_mode', ?) ON DUPLICATE KEY UPDATE stat_value = ?");
        $stmt->execute([$newState, $newState]);
        header('Location: admin.php?msg=maintenance_updated');
        exit;
    } catch (Exception $e) {
        // Fallback if table/key fails
    }
}

include 'includes/header.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !$isAdmin) {
    header('Location: index.php');
    exit;
}

// Fetch all users from DB
try {
    // Sicherstellen, dass die Spalte existiert
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS clan_meta TEXT DEFAULT NULL");
} catch (Exception $e) {
    // Falls IF NOT EXISTS nicht unterstützt wird
    try { $pdo->exec("ALTER TABLE users ADD COLUMN clan_meta TEXT DEFAULT NULL"); } catch (Exception $e2) {}
}

$stmt = $pdo->query("SELECT * FROM users WHERE is_bot = 0 ORDER BY username ASC");
$allUsers = $stmt->fetchAll();

// Automatische Migration von alten Clan-Daten falls vorhanden
try {
    foreach ($allUsers as &$u) {
        if ($u['is_clan_member'] && (empty($u['clan_meta']) || $u['clan_meta'] == 'null' || strlen($u['clan_meta']) < 10)) {
            // Wir suchen nach set_clan_member in der History
            $stmtMeta = $pdo->prepare("SELECT meta_value FROM pending_notifications WHERE discord_id = ? AND module_key = 'clan_management' AND meta_value IS NOT NULL AND meta_value != '' ORDER BY created_at DESC LIMIT 1");
            $stmtMeta->execute([$u['discord_id']]);
            $oldMeta = $stmtMeta->fetchColumn();
            
            if ($oldMeta) {
                // DEBUG: 
                // echo "<!-- Found Meta for " . $u['discord_id'] . ": " . $oldMeta . " -->";
                
                // Sicherstellen dass es valides JSON ist
                $decoded = json_decode($oldMeta, true);
                if ($decoded && (isset($decoded['ingame_name']) || isset($decoded['first_name']))) {
                    $pdo->prepare("UPDATE users SET clan_meta = ? WHERE discord_id = ?")->execute([$oldMeta, $u['discord_id']]);
                    $u['clan_meta'] = $oldMeta;
                }
            }
        }
    }
} catch (Exception $e) {
    // Migration überspringen bei Fehler
}
unset($u);

// Role configuration and sorting
$rolePriority = [
    '1499910122603020497' => '🌑〡Nox-Urvater',
    '1504599898698551336' => '🌘〡Nox-Vize',
    '1504740728767647835' => '⚙️〡Konstrukteur',
    '1504741301831208960' => '🎖️〡Teamleitung',
    '1504742849634238495' => '🏗️〡Architekten-Leitung',
    '1504743699635245056' => '🍃〡Farmerleitung',
    '1504742058261348352' => '🎉〡Eventleitung',
    '1504743817876738178' => '💻〡Code-Medium',
    '1504743897731956806' => '⚔️〡Nachtwache - [MOD]',
    '1504744004686975107' => '🛡️〡Mentor - [SUP]',
    '1504744121892343808' => '🔨〡Architekt',
    '1504744193518604298' => '🪴〡Farmer',
    '1504744264826097706' => '⚡〡VIP',
    '1504744338062704742' => '🛠️〡OPSUCHT - TEAM',
    '1504744397168967811' => '📜〡Ahne - Ehm. Leitung',
    '1504744480245415977' => '💜〡Herold - Freunde',
    '1504744554870603788' => '📹〡Visionär - Creator',
    '1502647723843653662' => '🌑〡Schattenkind',
    '1504601937943859200' => '🦍〡Besucher:in'
];

$groupedUsers = [];
foreach ($rolePriority as $id => $name) {
    $groupedUsers[$id] = [];
}
// For users with none of the above roles
$groupedUsers['other'] = [];

foreach ($allUsers as $user) {
    $targetRoles = json_decode($user['roles'] ?? '[]', true);
    $found = false;
    
    // Nur Rollen prüfen, wenn der Nutzer noch auf dem Discord ist
    if ($user['is_on_discord']) {
        foreach ($rolePriority as $roleId => $roleName) {
            if (in_array($roleId, $targetRoles)) {
                $groupedUsers[$roleId][] = $user;
                $found = true;
                break; // Nur der höchsten Priorität zuordnen
            }
        }
    }
    
    if (!$found) {
        $groupedUsers['other'][] = $user;
    }
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
                    <a href="admin.php" class="active"><i class="fas fa-users"></i> Mitgliederverwaltung</a>
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
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <div>
                        <span class="header-breadcrumb"><i class="fas fa-user-shield"></i> Administration</span>
                        <h1 class="glow-text">Mitgliederverwaltung</h1>
                        <p>Verwalte alle Discord-Mitglieder, Nox-Points und den Clan-Status.</p>
                    </div>
                    <div class="maintenance-toggle-box">
                        <form method="POST" id="maintenanceForm">
                            <input type="hidden" name="action" value="toggle_maintenance">
                            <input type="hidden" name="maintenance_state" value="<?php echo $maintenanceMode ? '0' : '1'; ?>">
                            <button type="submit" class="btn-minecraft <?php echo $maintenanceMode ? 'danger' : ''; ?>" style="padding: 10px 20px; font-size: 0.8rem;">
                                <span class="btn-text"><i class="fas fa-hammer"></i> <?php echo $maintenanceMode ? 'Wartung Beenden' : 'Wartung Aktivieren'; ?></span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="content-box">
                <div class="box-header">
                    <div class="header-left">
                        <h3><i class="fas fa-list"></i> Mitgliederliste</h3>
                    </div>
                    <div class="header-right">
                        <div class="header-search">
                            <i class="fas fa-search"></i>
                            <input type="text" id="memberSearch" placeholder="Suchen nach Name oder ID...">
                        </div>
                        <button class="btn-secondary" id="syncMembersBtn" title="Alle Mitglieder von Discord synchronisieren">
                            <i class="fas fa-sync-alt"></i> Aktualisieren
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="admin-table" id="memberTable">
                        <thead>
                            <tr>
                                <th>Mitglied</th>
                                <th>Status</th>
                                <th>Punkte</th>
                                <th>Beigetreten</th>
                                <th>Rang</th>
                                <th class="text-right">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            foreach ($groupedUsers as $roleId => $users): 
                                if (empty($users)) continue;
                                
                                $roleName = $rolePriority[$roleId] ?? 'Sonstige';
                            ?>
                                <tr class="role-header-row">
                                    <td colspan="7"><?php echo $roleName; ?></td>
                                </tr>
                                
                                <?php foreach ($users as $user): 
                                    $clanMeta = json_decode($user['clan_meta'] ?? '{}', true);
                                    $ingameName = $clanMeta['ingame_name'] ?? '';
                                    $displayUsername = $user['username'];
                                    $displayIngameName = $ingameName;
                                    $headName = !empty($displayIngameName) ? str_replace('.', '', $displayIngameName) : str_replace('.', '', $displayUsername);
                                    
                                    $avatarUrl = $user['avatar'] ? "https://cdn.discordapp.com/avatars/{$user['discord_id']}/{$user['avatar']}.png" : "assets/img/default_avatar.png";
                                ?>
                                    <tr class="member-row" data-username="<?php echo htmlspecialchars($user['username']); ?>" data-discord-id="<?php echo $user['discord_id']; ?>">
                                        <td>
                                            <div class="member-info">
                                                <img src="<?php echo $avatarUrl; ?>" alt="Avatar" class="table-avatar">
                                                <div class="member-meta">
                                                    <span class="member-name profile-trigger" 
                                                          onclick="openProfileModal(<?php echo htmlspecialchars(json_encode($user)); ?>)"
                                                          title="Profil ansehen">
                                                        <?php echo htmlspecialchars($displayUsername); ?>
                                                    </span>
                                                    <span class="member-role-tag"><?php echo $rolePriority[$roleId] ?? 'Mitglied'; ?></span>
                                                    <span class="member-id">ID: <?php echo $user['discord_id']; ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($user['is_clan_member']): ?>
                                                <span class="status-icon success" title="Clan-Mitglied"><i class="fas fa-check-circle"></i></span>
                                            <?php else: ?>
                                                <span class="status-icon danger" title="Kein Mitglied"><i class="fas fa-times-circle"></i></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="points-cell" style="<?php echo !($webPermission === 'Administrator' || $webPermission === 'GW-Manager') ? 'filter: grayscale(1); opacity: 0.5;' : ''; ?>">
                                            <span class="points-val"><?php echo number_format($user['points'], 0, ',', '.'); ?></span>
                                        </td>
                                        <td>
                                            <span class="join-date"><?php echo $user['joined_at'] ? date('d.m.Y', strtotime($user['joined_at'])) : 'Unbekannt'; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($webPermission === 'Administrator'): ?>
                                                <select class="rank-select" onchange="updateRank('<?php echo $user['discord_id']; ?>', this.value)">
                                                    <option value="Administrator" <?php echo $user['web_permission'] == 'Administrator' ? 'selected' : ''; ?>>Admin</option>
                                                    <option value="Bewerbung" <?php echo $user['web_permission'] == 'Bewerbung' ? 'selected' : ''; ?>>Bewerbung</option>
                                                    <option value="GW-Manager" <?php echo $user['web_permission'] == 'GW-Manager' ? 'selected' : ''; ?>>GW-Manager</option>
                                                    <option value="User" <?php echo ($user['web_permission'] == 'User' || empty($user['web_permission'])) ? 'selected' : ''; ?>>User</option>
                                                </select>
                                            <?php else: ?>
                                                <span class="member-role-tag"><?php echo htmlspecialchars($user['web_permission'] ?? 'User'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right">
                                            <div class="action-buttons">
                                                <?php 
                                                    $canManagePoints = ($webPermission === 'Administrator' || $webPermission === 'GW-Manager');
                                                    $canManageMembers = ($webPermission === 'Administrator' || $webPermission === 'Bewerbung');
                                                ?>
                                                <button class="btn-action btn-points <?php echo !$canManagePoints ? 'disabled' : ''; ?>" 
                                                        onclick="<?php echo $canManagePoints ? "openPointsModal('{$user['discord_id']}', '".htmlspecialchars($user['username'])."', {$user['points']})" : "return false;"; ?>" 
                                                        title="<?php echo $canManagePoints ? 'Punkte verwalten' : 'Keine Berechtigung'; ?>">
                                                    <i class="fas fa-coins"></i>
                                                </button>
                                                <button class="btn-action btn-history" 
                                                        onclick="openHistoryModal('<?php echo $user['discord_id']; ?>', '<?php echo htmlspecialchars($user['username']); ?>')" 
                                                        title="Historie ansehen">
                                                    <i class="fas fa-history"></i>
                                                </button>
                                                <button class="btn-action btn-level <?php echo $webPermission !== 'Administrator' ? 'disabled' : ''; ?>" 
                                                        onclick="<?php echo $webPermission === 'Administrator' ? "openLevelModal('{$user['discord_id']}', '".htmlspecialchars($user['username'])."', {$user['level']}, {$user['xp']})" : "return false;"; ?>" 
                                                        title="<?php echo $webPermission === 'Administrator' ? 'Level verwalten' : 'Keine Berechtigung'; ?>"
                                                        style="background: rgba(168, 85, 247, 0.1); color: #a855f7; border-color: rgba(168, 85, 247, 0.2);">
                                                    <i class="fas fa-layer-group"></i>
                                                </button>
                                                <?php if ($user['is_clan_member']): ?>
                                                    <button class="btn-action btn-remove <?php echo !$canManageMembers ? 'disabled' : ''; ?>" 
                                                            onclick="<?php echo $canManageMembers ? "removeClanMember('{$user['discord_id']}', '".htmlspecialchars($user['username'])."')" : "return false;"; ?>" 
                                                            title="<?php echo $canManageMembers ? 'Aus Clan entfernen' : 'Keine Berechtigung'; ?>">
                                                        <i class="fas fa-user-minus"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn-action btn-clan <?php echo !$canManageMembers ? 'disabled' : ''; ?>" 
                                                            onclick="<?php echo $canManageMembers ? "openClanModal('{$user['discord_id']}', '".htmlspecialchars($user['username'])."')" : "return false;"; ?>" 
                                                            title="<?php echo $canManageMembers ? 'Als Clan-Mitglied setzen' : 'Keine Berechtigung'; ?>">
                                                        <i class="fas fa-user-plus"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
<!-- Profile Information Modal -->
<div id="profileModal" class="modal profile-modal-wrapper">
    <div class="modal-content profile-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-circle"></i> Profil-Informationen</h3>
            <span class="close" onclick="closeModal('profileModal')">&times;</span>
        </div>
        <div class="profile-body" id="profileModalBody">
            <!-- Dynamically filled -->
        </div>
    </div>
</div>

</section>

<!-- History Modal -->
<div id="historyModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
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
.history-item {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.2s;
}

.history-item:hover {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(176, 39, 245, 0.2);
}

.history-item.undone {
    opacity: 0.6;
    background: rgba(239, 68, 68, 0.02);
}

.history-details {
    flex: 1;
}

.history-action-name {
    font-weight: 700;
    color: #fff;
    font-size: 1rem;
    display: block;
    margin-bottom: 2px;
}

.history-meta {
    font-size: 0.8rem;
    color: var(--text-muted);
    display: flex;
    gap: 12px;
    margin-bottom: 5px;
}

.history-reason {
    font-size: 0.85rem;
    color: rgba(255,255,255,0.8);
    background: rgba(0,0,0,0.2);
    padding: 5px 10px;
    border-radius: 4px;
    display: inline-block;
}

.badge-undone {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.2);
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.7rem;
    margin-left: 8px;
}
</style>
<div id="pointsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-coins"></i> Nox-Points Management</h3>
            <span class="close" onclick="closeModal('pointsModal')">&times;</span>
        </div>
        <form id="pointsForm">
            <input type="hidden" name="discord_id" id="points_discord_id">
            <div class="form-group">
                <label>Nutzer</label>
                <div class="current-points"><span id="points_username" class="highlight"></span></div>
            </div>
            <div class="form-group">
                <label>Aktuelle Punkte</label>
                <div class="current-points"><span id="points_current" class="highlight"></span></div>
            </div>
            <div class="form-group">
                <label for="points_action">Aktion</label>
                <select name="points_action" id="points_action" class="form-control">
                    <option value="set">Punkte setzen</option>
                    <option value="add">Punkte hinzufügen</option>
                    <option value="remove">Punkte abziehen</option>
                    <option value="reset">Punkte zurücksetzen</option>
                </select>
            </div>
            <div class="form-group" id="points_amount_group">
                <label for="points_amount">Betrag</label>
                <input type="number" name="amount" id="points_amount" class="form-control" min="0" value="0">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('pointsModal')">Abbrechen</button>
                <button type="submit" class="btn-primary">Speichern</button>
            </div>
        </form>
    </div>
</div>

<!-- Clan Member Modal -->
<div id="clanModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Als Clan-Mitglied setzen</h3>
            <span class="close" onclick="closeModal('clanModal')">&times;</span>
        </div>
        <form id="clanForm">
            <input type="hidden" name="discord_id" id="clan_discord_id">
            <div class="form-group">
                <label>Nutzer</label>
                <div class="current-points"><span id="clan_username" class="highlight"></span></div>
            </div>
            <div class="form-row">
                <div class="form-group col-6">
                    <label for="first_name">Vorname (optional)</label>
                    <input type="text" name="first_name" id="first_name" class="form-control" placeholder="z.B. Max">
                </div>
                <div class="form-group col-6">
                    <label for="ingame_name">Ingame-Name</label>
                    <input type="text" name="ingame_name" id="ingame_name" class="form-control" required placeholder="z.B. NoxusPvP">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-6">
                    <label for="age">Alter</label>
                    <input type="number" name="age" id="age" class="form-control" required min="0">
                </div>
                <div class="form-group col-6">
                    <label for="join_date">OPSUCHT Join-Datum</label>
                    <input type="date" name="join_date" id="join_date" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label for="strengths_weaknesses">Stärken / Schwächen</label>
                <textarea name="strengths_weaknesses" id="strengths_weaknesses" class="form-control" rows="3" required placeholder="Was zeichnet dich aus?"></textarea>
            </div>
            <div class="form-group">
                <label for="recruiter_search">Angeworben von (optional)</label>
                <div style="position: relative;">
                    <input type="text" id="recruiter_search" class="form-control" placeholder="Nach Mitglied suchen..." autocomplete="off">
                    <input type="hidden" name="recruiter_id" id="recruiter_id">
                    <div id="recruiter_results" class="search-results-dropdown"></div>
                </div>
            </div>
            <div class="form-group">
                <label for="clan_web_permission">Web-Rang</label>
                <select name="web_permission" id="clan_web_permission" class="form-control">
                    <option value="User">Standard (User)</option>
                    <option value="Bewerbung">Bewerbung</option>
                    <option value="GW-Manager">GW-Manager</option>
                    <?php if ($webPermission === 'Administrator'): ?>
                        <option value="Administrator">Administrator</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('clanModal')">Abbrechen</button>
                <button type="submit" class="btn-primary">Bestätigen & Aufnehmen</button>
            </div>
        </form>
    </div>
</div>

<!-- Level Management Modal -->
<div id="levelModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-layer-group"></i> Level & XP Management</h3>
            <span class="close" onclick="closeModal('levelModal')">&times;</span>
        </div>
        <form id="levelForm">
            <input type="hidden" name="discord_id" id="level_discord_id">
            <div class="form-group">
                <label>Nutzer</label>
                <div class="current-points"><span id="level_username" class="highlight"></span></div>
            </div>
            <div class="form-row">
                <div class="form-group col-6">
                    <label>Aktuelles Level</label>
                    <div class="current-points"><span id="level_current" class="highlight"></span></div>
                </div>
                <div class="form-group col-6">
                    <label>Aktuelle XP</label>
                    <div class="current-points"><span id="xp_current" class="highlight"></span></div>
                </div>
            </div>
            <div class="form-group">
                <label for="level_action">Aktion</label>
                <select name="level_action" id="level_action" class="form-control">
                    <option value="set">Level setzen</option>
                    <option value="add">Level +1 (oder mehr)</option>
                    <option value="remove">Level -1 (oder mehr)</option>
                    <option value="set_xp">XP setzen</option>
                    <option value="reset_xp">XP zurücksetzen</option>
                    <option value="reset">Kompletter Reset (Lvl 0, 0 XP)</option>
                </select>
            </div>
            <div class="form-group" id="level_value_group">
                <label for="level_value">Wert</label>
                <input type="number" name="value" id="level_value" class="form-control" min="0" value="1">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('levelModal')">Abbrechen</button>
                <button type="submit" class="btn-primary">Speichern</button>
            </div>
        </form>
    </div>
</div>

<style>
.btn-action.disabled {
    filter: grayscale(1);
    opacity: 0.3;
    cursor: not-allowed !important;
}

.rank-select {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 4px;
    color: white;
    padding: 2px 5px;
    font-size: 0.8rem;
    cursor: pointer;
    outline: none;
}

.rank-select:focus {
    border-color: #B027F5;
}

.admin-page .dashboard-main {
    max-width: 1200px;
}

.box-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.header-left, .header-right {
    display: flex;
    align-items: center;
    gap: 10px;
}

.header-search {
    position: relative;
    display: flex;
    align-items: center;
}

.header-search i {
    position: absolute;
    left: 12px;
    color: rgba(255,255,255,0.5);
}

.header-search input {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    padding: 8px 12px 8px 35px;
    color: white;
    width: 250px;
    transition: all 0.3s ease;
}

.header-search input:focus {
    background: rgba(255,255,255,0.1);
    border-color: #B027F5;
    outline: none;
    width: 300px;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.admin-table th {
    text-align: left;
    padding: 12px 15px;
    border-bottom: 2px solid rgba(255,255,255,0.05);
    color: rgba(255,255,255,0.6);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
}

.admin-table td {
    padding: 12px 15px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    vertical-align: middle;
}

.member-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.table-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 2px solid rgba(255,255,255,0.1);
}

.member-meta {
    display: flex;
    flex-direction: column;
}

.member-name {
    font-weight: 600;
}

.member-role-tag {
    font-size: 0.7rem;
    color: #B027F5;
    background: rgba(176, 39, 245, 0.1);
    padding: 1px 6px;
    border-radius: 4px;
    width: fit-content;
    margin: 2px 0;
}

.member-id {
    font-size: 0.75rem;
    color: rgba(255,255,255,0.4);
}

.status-icon {
    font-size: 1.2rem;
}

.status-icon.success { color: #4caf50; }
.status-icon.danger { color: #f44336; }

.points-cell {
    font-family: 'Inter', sans-serif;
    font-weight: 600;
    color: #ffd700;
}

.action-buttons {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}

.btn-action {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    color: white;
}

.btn-points { background: rgba(255, 215, 0, 0.15); color: #ffd700; }
.btn-points:hover { background: #ffd700; color: #000; }

.btn-clan { background: rgba(176, 39, 245, 0.15); color: #B027F5; }
.btn-clan:hover { background: #B027F5; color: #fff; }

.btn-remove { background: rgba(244, 67, 54, 0.15); color: #f44336; }
.btn-remove:hover { background: #f44336; color: #fff; }

.btn-history { background: rgba(0, 123, 255, 0.15); color: #007bff; }
.btn-history:hover { background: #007bff; color: #fff; }

/* Modal Styles */
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
}

.modal-content {
    background: #1a1a2e;
    margin: 8% auto;
    padding: 0;
    border: 1px solid rgba(255,255,255,0.1);
    width: 90%;
    max-width: 500px;
    border-radius: 16px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    overflow: hidden;
    animation: modalFadeIn 0.3s ease-out;
}

@keyframes modalFadeIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

.modal-header {
    padding: 20px 25px;
    background: rgba(255, 255, 255, 0.03);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 { margin: 0; display: flex; align-items: center; gap: 10px; font-size: 1.2rem; color: #fff; }

.close {
    color: #aaa;
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.2s;
}

.close:hover { color: white; transform: scale(1.1); }

.form-group { margin-bottom: 20px; padding: 0 25px; }
form > .form-group:first-child, form > .form-row:first-child { margin-top: 40px; }
.form-row { display: flex; gap: 20px; padding: 0 25px; margin-bottom: 20px; align-items: flex-start; }
.form-row .form-group { padding: 0; margin-bottom: 0; flex: 1 1 0; width: 0; }
.col-6 { flex: 1 1 0; width: 0; }

label { display: block; margin-bottom: 10px; font-size: 0.85rem; font-weight: 600; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.5px; }

.form-control {
    width: 100%;
    background: rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(176, 39, 245, 0.2);
    border-radius: 10px;
    padding: 12px 15px;
    color: white;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    outline: none;
}

.form-control:focus { 
    border-color: #B027F5; 
    background: rgba(176, 39, 245, 0.05);
    box-shadow: 0 0 15px rgba(176, 39, 245, 0.15);
}

select.form-control option {
    background-color: #1a1a2e;
    color: white;
}

.current-points {
    margin-top: 10px;
    padding: 10px;
    background: rgba(176, 39, 245, 0.05);
    border-radius: 8px;
    border: 1px solid rgba(176, 39, 245, 0.1);
    font-size: 0.9rem;
}

.highlight { color: #B027F5; font-weight: 700; }

.search-results-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #1a1a2e;
    border: 1px solid rgba(176, 39, 245, 0.3);
    border-radius: 0 0 10px 10px;
    z-index: 1000;
    max-height: 200px;
    overflow-y: auto;
    display: none;
    box-shadow: 0 10px 25px rgba(0,0,0,0.5);
}

.search-result-item {
    padding: 10px 15px;
    cursor: pointer;
    transition: all 0.2s;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    display: flex;
    align-items: center;
    gap: 10px;
}

.search-result-item:hover {
    background: rgba(176, 39, 245, 0.1);
}

.search-result-item img {
    width: 24px;
    height: 24px;
    border-radius: 50%;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 20px 25px;
    background: rgba(0, 0, 0, 0.1);
    border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.btn-primary {
    background: linear-gradient(135deg, #B027F5 0%, #8a1ec1 100%);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(176, 39, 245, 0.2);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(176, 39, 245, 0.4);
    filter: brightness(1.1);
}

.btn-secondary {
    background: rgba(255,255,255,0.05);
    color: white;
    border: 1px solid rgba(255,255,255,0.1);
    padding: 12px 25px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-secondary:hover {
    background: rgba(255,255,255,0.1);
    border-color: rgba(255,255,255,0.2);
}

.text-right { text-align: right; }
.text-center { text-align: center; }

.role-header-row td {
    background: rgba(176, 39, 245, 0.1);
    color: #B027F5;
    font-weight: 700;
    padding: 15px !important;
    font-size: 0.9rem;
    letter-spacing: 0.5px;
    border-left: 4px solid #B027F5;
}

.empty-role-row td {
    color: rgba(255, 255, 255, 0.3);
    font-style: italic;
    padding: 20px !important;
}
.member-name.profile-trigger {
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
    display: inline-block;
}

.member-name.profile-trigger:hover {
    color: var(--primary-purple);
    text-shadow: 0 0 10px rgba(125, 64, 255, 0.5);
}

.profile-modal-wrapper {
    backdrop-filter: blur(12px) !important;
}

.profile-content {
    max-width: 600px !important;
    border: 1px solid rgba(125, 64, 255, 0.2) !important;
    box-shadow: 0 0 40px rgba(0, 0, 0, 0.8), 0 0 20px rgba(125, 64, 255, 0.1) !important;
}

.profile-header-main {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 10px;
    padding: 25px 25px 20px 25px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.profile-avatar-large {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 3px solid var(--primary-purple);
    box-shadow: 0 0 15px rgba(125, 64, 255, 0.3);
}

.profile-names h2 {
    margin: 0;
    font-size: 1.8rem;
    font-weight: 800;
}

.profile-names .discord-id {
    font-family: monospace;
    color: var(--text-muted);
    font-size: 0.9rem;
}

.profile-section-title {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--primary-purple);
    margin: 20px 0 10px 0;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
}

.profile-roles-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.role-badge {
    padding: 4px 10px;
    background: rgba(125, 64, 255, 0.1);
    border: 1px solid rgba(125, 64, 255, 0.2);
    border-radius: 6px;
    font-size: 0.8rem;
    color: white;
}

.profile-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    padding: 0 25px;
}

.profile-info-item {
    margin-bottom: 15px;
}

.profile-info-item label {
    display: block;
    margin-bottom: 8px;
    font-size: 0.75rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.4);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.profile-info-value {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    padding: 12px 15px;
    color: white;
    font-size: 1rem;
    font-weight: 700;
    min-height: 48px;
    display: flex;
    align-items: center;
}

.profile-info-value.highlight {
    color: var(--primary-purple);
    border-color: rgba(125, 64, 255, 0.2);
    background: rgba(125, 64, 255, 0.05);
}

.profile-meta-box {
    margin: 0 25px 25px 25px;
    padding: 15px;
    background: rgba(125, 64, 255, 0.05);
    border-radius: 12px;
    border: 1px solid rgba(125, 64, 255, 0.1);
    font-size: 0.95rem;
    line-height: 1.6;
    color: rgba(255, 255, 255, 0.9);
}

.profile-section-title {
    padding: 0 25px;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--primary-purple);
    margin: 25px 0 15px 0;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 10px;
}

.profile-roles-list {
    padding: 0 25px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

</style>

<script>
const rolePriorityConfig = <?php echo json_encode($rolePriority); ?>;
const allMembers = <?php echo json_encode($allUsers); ?>;
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
    body.innerHTML = '<div class="text-center" style="padding: 20px;"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Lade Historie...</p></div>';
    document.getElementById('historyModal').style.display = 'block';
    document.body.classList.add('modal-open');

    fetch(`api/admin_actions.php?action=get_user_history&target_id=${userId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (data.history.length === 0) {
                    body.innerHTML = '<p class="text-center" style="color: var(--text-muted); padding: 20px;">Keine Moderations-Einträge gefunden.</p>';
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
                body.innerHTML = `<p class="text-center" style="color: #f44336; padding: 20px;">Fehler: ${data.error}</p>`;
            }
        });
}

async function undoModAction(logId, type) {
    if (await showConfirm('Möchtest du diese Aktion wirklich rückgängig machen?')) {
        const formData = new FormData();
        formData.append('action', 'undo_mod_action');
        formData.append('log_id', logId);

        fetch('api/admin_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showNotification('Aktion wurde rückgängig gemacht!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification('Fehler: ' + data.error, 'error');
            }
        });
    }
}

function openProfileModal(user) {
    const body = document.getElementById('profileModalBody');
    const roles = JSON.parse(user.roles || '[]');
    const clanMeta = JSON.parse(user.clan_meta || '{}');
    const ingameName = clanMeta.ingame_name || '';
    
    // Namen für Anzeige (Punkte bleiben)
    const displayUsername = user.username;
    const displayIngameName = ingameName;
    
    // Name für Minecraft-Head (Punkte entfernen)
    const headSource = (displayIngameName && displayIngameName !== 'Unbekannt') ? displayIngameName.replace(/\./g, '') : displayUsername.replace(/\./g, '');
    
    // Haupt-Avatar ist IMMER Discord
    const avatarUrl = user.avatar ? `https://cdn.discordapp.com/avatars/${user.discord_id}/${user.avatar}.png` : 'assets/img/default_avatar.png';
    
    // Sort roles by priority
    const sortedRoles = [];
    Object.keys(rolePriorityConfig).forEach(roleId => {
        if (roles.includes(roleId)) {
            sortedRoles.push(rolePriorityConfig[roleId]);
        }
    });

    let clanInfoHtml = '';
    if (user.is_clan_member == 1) {
        clanInfoHtml = `
            <div class="profile-section-title"><i class="fas fa-shield-alt"></i> Clan-Informationen</div>
            <div class="profile-info-grid">
                <div class="profile-info-item">
                    <label>Ingame-Name</label>
                    <div class="profile-info-value">${displayIngameName || 'Nicht angegeben'}</div>
                </div>
                <div class="profile-info-item">
                    <label>Vorname</label>
                    <div class="profile-info-value">${clanMeta.first_name || '-'}</div>
                </div>
                <div class="profile-info-item">
                    <label>Alter</label>
                    <div class="profile-info-value">${clanMeta.age || '-'}</div>
                </div>
                <div class="profile-info-item">
                    <label>Opsucht-Join</label>
                    <div class="profile-info-value">${clanMeta.join_date ? new Date(clanMeta.join_date).toLocaleDateString('de-DE') : '-'}</div>
                </div>
            </div>
            <div class="profile-section-title"><i class="fas fa-star"></i> Stärken & Schwächen</div>
            <div class="profile-meta-box">${clanMeta.strengths_weaknesses || 'Keine Angabe'}</div>
        `;
    }

    body.innerHTML = `
        <div class="profile-header-main">
            <img src="${avatarUrl}" alt="Avatar" class="profile-avatar-large">
            <div class="profile-names">
                <h2>${displayUsername}</h2>
                <div class="discord-id">ID: ${user.discord_id}</div>
                <button class="btn-minecraft" onclick="openHistoryModal('${user.discord_id}', '${user.username}')" style="margin-top: 10px; font-size: 0.7rem; padding: 5px 12px;">
                    <i class="fas fa-history"></i> Historie
                </button>
            </div>
        </div>
        
        <div class="profile-section-title"><i class="fas fa-tags"></i> Rollen</div>
        <div class="profile-roles-list">
            ${sortedRoles.length > 0 ? sortedRoles.map(r => `<span class="role-badge">${r}</span>`).join('') : '<span class="role-badge">Keine Rollen</span>'}
        </div>

        <div class="profile-section-title"><i class="fas fa-calendar-alt"></i> Account-Info</div>
        <div class="profile-info-grid">
            <div class="profile-info-item">
                <label>Punkte</label>
                <div class="profile-info-value highlight">${parseInt(user.points).toLocaleString()}</div>
            </div>
            <div class="profile-info-item">
                <label>D-Join</label>
                <div class="profile-info-value">${user.joined_at ? new Date(user.joined_at).toLocaleDateString('de-DE') : 'Unbekannt'}</div>
            </div>
            <div class="profile-info-item" style="grid-column: span 2;">
                <label>Clan-Status</label>
                <div class="profile-info-value">${user.is_clan_member == 1 ? '✅ Mitglied' : '❌ Kein Mitglied'}</div>
            </div>
        </div>

        ${clanInfoHtml}
        <div style="height: 25px;"></div>
    `;

    document.getElementById('profileModal').style.display = 'block';
    document.body.classList.add('modal-open');
}

function openPointsModal(id, username, current) {
    document.getElementById('points_discord_id').value = id;
    document.getElementById('points_username').innerText = username;
    document.getElementById('points_current').innerText = current.toLocaleString();
    document.getElementById('pointsModal').style.display = 'block';
    document.body.classList.add('modal-open');
}

function openLevelModal(id, username, level, xp) {
    document.getElementById('level_discord_id').value = id;
    document.getElementById('level_username').innerText = username;
    document.getElementById('level_current').innerText = level;
    document.getElementById('xp_current').innerText = xp.toLocaleString();
    document.getElementById('levelModal').style.display = 'block';
    document.body.classList.add('modal-open');
}

function openClanModal(id, username) {
    document.getElementById('clan_discord_id').value = id;
    document.getElementById('clan_username').innerText = username;
    document.getElementById('clanModal').style.display = 'block';
    document.body.classList.add('modal-open');
}

async function removeClanMember(id, username) {
    if (await showConfirm('Bist du sicher, dass du ' + username + ' aus dem Clan entfernen möchtest? Alle Clan-Rollen werden entzogen.')) {
        fetch('api/admin_actions.php?action=remove_clan_member', {
            method: 'POST',
            body: new URLSearchParams({
                'discord_id': id
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showNotification('Mitglied erfolgreich entfernt!', 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showNotification('Fehler: ' + data.message, 'error');
            }
        });
    }
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
    
    // Nur entfernen wenn kein anderes Modal mehr offen ist
    const openModals = document.querySelectorAll('.modal[style*="display: block"]');
    if (openModals.length === 0) {
        document.body.classList.remove('modal-open');
    }
}

// Search Functionality
document.getElementById('memberSearch').addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.member-row');
    
    rows.forEach(row => {
        const name = row.getAttribute('data-username').toLowerCase();
        const id = row.getAttribute('data-discord-id').toLowerCase();
        
        if (name.includes(term) || id.includes(term)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

function updateRank(discordId, newRank) {
    const formData = new FormData();
    formData.append('discord_id', discordId);
    formData.append('web_permission', newRank);

    fetch('api/admin_actions.php?action=update_web_permission', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showNotification('Rang erfolgreich aktualisiert!', 'success');
        } else {
            showNotification('Fehler: ' + data.message, 'error');
        }
    })
    .catch(err => {
        showNotification('Ein Fehler ist aufgetreten.', 'error');
    });
}

// Recruiter Search Functionality
const recruiterSearch = document.getElementById('recruiter_search');
const recruiterResults = document.getElementById('recruiter_results');
const recruiterIdInput = document.getElementById('recruiter_id');

recruiterSearch.addEventListener('input', function() {
    const term = this.value.toLowerCase();
    if (term.length < 2) {
        recruiterResults.style.display = 'none';
        return;
    }

    const filtered = allMembers.filter(m => 
        m.username.toLowerCase().includes(term) || 
        m.discord_id.includes(term)
    ).slice(0, 5);

    if (filtered.length > 0) {
        recruiterResults.innerHTML = filtered.map(m => `
            <div class="search-result-item" onclick="selectRecruiter('${m.discord_id}', '${m.username}')">
                <img src="${m.avatar ? `https://cdn.discordapp.com/avatars/${m.discord_id}/${m.avatar}.png` : 'assets/img/default_avatar.png'}" alt="Avatar">
                <div style="display: flex; flex-direction: column;">
                    <span style="font-weight: 600; font-size: 0.9rem;">${m.username}</span>
                    <span style="font-size: 0.7rem; color: rgba(255,255,255,0.4);">ID: ${m.discord_id}</span>
                </div>
            </div>
        `).join('');
        recruiterResults.style.display = 'block';
    } else {
        recruiterResults.style.display = 'none';
    }
});

function selectRecruiter(id, username) {
    recruiterIdInput.value = id;
    recruiterSearch.value = username;
    recruiterResults.style.display = 'none';
    recruiterSearch.style.borderColor = '#2ecc71';
}

// Close search results when clicking outside
document.addEventListener('click', function(e) {
    if (e.target !== recruiterSearch && e.target !== recruiterResults) {
        recruiterResults.style.display = 'none';
    }
});

// Form Submissions
document.getElementById('syncMembersBtn').addEventListener('click', function() {
    const btn = this;
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Synchronisiere...';
    btn.disabled = true;

    fetch('api/admin_actions.php?action=sync_members', {
        method: 'POST'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showNotification('Mitglieder erfolgreich synchronisiert!', 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showNotification('Fehler bei der Synchronisation: ' + data.message, 'error');
            btn.innerHTML = originalContent;
            btn.disabled = false;
        }
    })
    .catch(err => {
        showNotification('Ein Fehler ist aufgetreten.', 'error');
        btn.innerHTML = originalContent;
        btn.disabled = false;
    });
});

document.getElementById('pointsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('api/admin_actions.php?action=manage_points', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showNotification('Fehler: ' + data.message, 'error');
        }
    });
});

document.getElementById('clanForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('api/admin_actions.php?action=set_clan_member', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showNotification('Mitglied erfolgreich aufgenommen! Der Bot wird nun die Rollen anpassen und die DM senden.', 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showNotification('Fehler: ' + data.message, 'error');
        }
    });
});

document.getElementById('levelForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('api/admin_actions.php?action=manage_levels', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showNotification('Fehler: ' + data.message, 'error');
        }
    });
});

let modalClicked = false;
window.addEventListener('mousedown', function(event) {
    if (event.target.classList.contains('modal')) {
        modalClicked = true;
    } else {
        modalClicked = false;
    }
});

window.onclick = function(event) {
    if (modalClicked && event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        
        // Check if any other modal is still open
        const openModals = document.querySelectorAll('.modal[style*="display: block"]');
        if (openModals.length === 0) {
            document.body.classList.remove('modal-open');
        }
    }
}
</script>

<?php include 'includes/footer.php'; ?>
