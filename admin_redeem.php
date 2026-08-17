<?php
include 'includes/header.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !($webPermission === 'Administrator' || $webPermission === 'GW-Manager')) {
    header('Location: index.php');
    exit;
}

// Fetch some stats for the header if needed
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM user_inventory WHERE code IS NOT NULL");
    $activeCodesCount = $stmt->fetchColumn();
} catch (Exception $e) {
    $activeCodesCount = 0;
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
                <h1 class="glow-text">Gutschein-System</h1>
                <p>Prüfe und löse generierte Item-Codes aus dem Nutzer-Inventar ein.</p>
            </div>

            <div class="admin-content-grid" style="grid-template-columns: 1fr;">
                <div class="content-box">
                    <div class="box-header">
                        <h3><i class="fas fa-ticket-alt"></i> Code einlösen</h3>
                    </div>
                    
                    <div class="redeem-container" style="max-width: 600px; margin: 20px 0;">
                        <div class="form-group">
                            <label for="redeem_code_input">Gutscheincode eingeben</label>
                            <div style="display: flex; gap: 15px;">
                                <input type="text" id="redeem_code_input" class="form-control" placeholder="z.B. AB12CD34" style="font-size: 1.2rem; padding: 15px; letter-spacing: 2px; font-family: monospace; text-transform: uppercase;">
                                <button type="button" class="btn-primary" onclick="checkRedeemCode()" style="padding: 0 30px; font-weight: 700;">Prüfen</button>
                            </div>
                        </div>

                        <div id="code_info_display" style="display: none; margin-top: 30px; padding: 25px; background: rgba(255,255,255,0.03); border-radius: 16px; border: 1px solid rgba(168, 85, 247, 0.2); border-left: 5px solid #a855f7; animation: fadeIn 0.3s ease-out;">
                            <!-- Content will be injected by JS -->
                        </div>
                    </div>
                </div>

                <div class="content-box" style="margin-top: 30px;">
                    <div class="box-header">
                        <h3><i class="fas fa-info-circle"></i> Anleitung</h3>
                    </div>
                    <div style="padding: 20px; color: var(--text-muted); line-height: 1.6;">
                        <p>Hier kannst du Codes einlösen, die Nutzer in ihrem Inventar generiert haben. </p>
                        <ul style="margin-top: 10px; padding-left: 20px;">
                            <li>Gib den 8-stelligen Code ein und klicke auf "Prüfen".</li>
                            <li>Überprüfe die Informationen (Nutzer, Item, Gültigkeit).</li>
                            <li>Klicke auf "Jetzt einlösen", um das Item aus dem Inventar zu entfernen und den Vorgang abzuschließen.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
</section>

<script>
function checkRedeemCode() {
    const code = document.getElementById('redeem_code_input').value.trim();
    if (!code) return;

    fetch('api/shop_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=check_code&code=${code}`
    })
    .then(res => res.json())
    .then(data => {
        const display = document.getElementById('code_info_display');
        if (data.success) {
            display.innerHTML = `
                <div class="code-details">
                    <h2 style="color: #fff; margin-bottom: 15px;">Gutschein Details</h2>
                    <div class="detail-row" style="margin-bottom: 20px;">
                        <div style="font-size: 0.8rem; color: #a855f7; text-transform: uppercase; font-weight: 700;">Produkt</div>
                        <div style="font-size: 1.4rem; font-weight: 800; color: #fff;">${data.product_name}</div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                        <div>
                            <div style="font-size: 0.8rem; color: #a855f7; text-transform: uppercase; font-weight: 700;">Nutzer</div>
                            <div style="font-size: 1.1rem; color: #fff;">${data.username}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.8rem; color: #a855f7; text-transform: uppercase; font-weight: 700;">Status</div>
                            <div style="font-size: 1.1rem; color: #2ecc71;"><i class="fas fa-check-circle"></i> Gültig</div>
                        </div>
                    </div>
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 25px; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                        <div>Generiert am: ${data.generated_at}</div>
                        <div>Gültig bis: ${data.expires_at} (24h)</div>
                    </div>
                    <button class="btn-redeem-now" onclick="redeemCode('${code}')" style="background: #a855f7; color: white; border: none; padding: 15px; width: 100%; border-radius: 10px; font-weight: 800; font-size: 1rem; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px;">
                        <i class="fas fa-check-circle"></i> CODE JETZT EINLÖSEN
                    </button>
                </div>
            `;
            display.style.display = 'block';
        } else {
            showNotification('Fehler: ' + data.message, 'error');
            display.style.display = 'none';
        }
    });
}

async function redeemCode(code) {
    if (await showConfirm('Bist du sicher, dass du diesen Code einlösen möchtest? Das Item wird aus dem Inventar des Nutzers entfernt.')) {
        fetch('api/shop_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=redeem_code&code=${code}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showNotification('Code erfolgreich eingelöst! Das Item wurde aus dem Inventar entfernt.', 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showNotification('Fehler: ' + data.message, 'error');
            }
        });
    }
}
</script>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.form-control {
    width: 100%;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    padding: 12px;
    color: white;
    outline: none;
    transition: 0.3s;
}
.form-control:focus {
    border-color: #a855f7;
    background: rgba(255,255,255,0.08);
}
.btn-primary {
    background: #a855f7;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.3s;
}
.btn-primary:hover {
    background: #9333ea;
    transform: translateY(-2px);
}
.btn-redeem-now:hover {
    background: #9333ea;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(168, 85, 247, 0.4);
}
</style>

<?php include 'includes/footer.php'; ?>
