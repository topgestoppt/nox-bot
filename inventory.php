<?php
include 'includes/header.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

$discord_id = $_SESSION['user']['id'];

// Fetch user inventory
try {
    $stmt = $pdo->prepare("
        SELECT ui.*, sp.display_name, sp.description, sp.category, sp.item_id, sp.icon_url 
        FROM user_inventory ui
        JOIN shop_products sp ON ui.product_id = sp.id
        WHERE ui.discord_id = ?
        ORDER BY ui.purchased_at DESC
    ");
    $stmt->execute([$discord_id]);
    $rawInventory = $stmt->fetchAll();

    // Grouping / Stacking Logic
    $inventory = [];
    foreach ($rawInventory as $item) {
        $key = $item['item_id'];
        
        $remaining = getRemainingTime($item['code_generated_at']);
        $isCodeActive = ($remaining !== null && $remaining > 0);

        if (!isset($inventory[$key])) {
            $inventory[$key] = [
                'display_name' => $item['display_name'],
                'description' => $item['description'],
                'category' => $item['category'],
                'item_id' => $item['item_id'],
                'icon_url' => $item['icon_url'],
                'last_purchase' => $item['purchased_at'],
                'total_count' => 0,
                'active_codes' => [],
                'available_instances' => []
            ];
        }

        $inventory[$key]['total_count']++;
        
        // Keep the latest purchase date
        if (strtotime($item['purchased_at']) > strtotime($inventory[$key]['last_purchase'])) {
            $inventory[$key]['last_purchase'] = $item['purchased_at'];
        }

        if ($isCodeActive) {
            $inventory[$key]['active_codes'][] = [
                'id' => $item['id'],
                'code' => $item['code'],
                'expires_at' => strtotime($item['code_generated_at']) + (24 * 3600)
            ];
        } else {
            $inventory[$key]['available_instances'][] = $item['id'];
        }
    }

} catch (Exception $e) {
    $inventory = [];
    $dbError = $e->getMessage();
}

function getRemainingTime($generatedAt) {
    if (!$generatedAt) return null;
    $expiresAt = strtotime($generatedAt) + (24 * 3600);
    $remaining = $expiresAt - time();
    return $remaining > 0 ? $remaining : 0;
}
?>

<section class="inventory-section">
    <div class="container">
        <?php if (isset($dbError)): ?>
            <div class="alert-box error">
                <div class="alert-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="alert-content">
                    <h4>Datenbank-Fehler</h4>
                    <p>Die Inventar-Tabellen wurden noch nicht erstellt. Bitte führe das Setup aus.</p>
                    <a href="api/shop_db_setup.php" class="btn-setup">Setup ausführen</a>
                </div>
            </div>
        <?php endif; ?>

        <div class="inventory-header">
            <span class="header-tag">Dein Besitz</span>
            <h1><i class="fas fa-box-open"></i> Mein Inventar</h1>
            <p>Verwalte deine gekauften Items und generiere Einlöse-Codes für den Server.</p>
        </div>

        <div class="inventory-grid">
            <?php if (empty($inventory) && !isset($dbError)): ?>
                <div class="no-items-card">
                    <div class="empty-icon"><i class="fas fa-shopping-cart"></i></div>
                    <h3>Dein Inventar ist leer</h3>
                    <p>Du hast noch keine Items im Shop erworben.</p>
                    <a href="shop.php" class="btn-primary">Zum Shop</a>
                </div>
            <?php else: ?>
                <?php foreach ($inventory as $stackedItem): 
                    $hasAvailable = !empty($stackedItem['available_instances']);
                ?>
                    <div class="inventory-card">
                        <div class="card-main">
                            <div class="item-visual <?php echo $stackedItem['category']; ?>" <?php echo !empty($stackedItem['icon_url']) ? 'style="background: none; padding: 0;"' : ''; ?>>
                                <div class="item-icon-wrapper">
                                    <?php if (!empty($stackedItem['icon_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($stackedItem['icon_url']); ?>" alt="" style="width: 70px; height: 70px; object-fit: cover; border-radius: 20px;">
                                    <?php else: ?>
                                        <i class="fas <?php echo $stackedItem['category'] == 'item' ? 'fa-cube' : 'fa-shield-alt'; ?>"></i>
                                    <?php endif; ?>
                                    
                                    <?php if ($stackedItem['total_count'] > 1): ?>
                                        <span class="item-amount-badge">x<?php echo $stackedItem['total_count']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if (empty($stackedItem['icon_url'])): ?>
                                    <span class="category-label"><?php echo ucfirst($stackedItem['category']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="item-info">
                                <h3><?php echo htmlspecialchars($stackedItem['display_name']); ?></h3>
                                <p><?php echo htmlspecialchars($stackedItem['description']); ?></p>
                                <div class="item-meta">
                                    <span><i class="fas fa-fingerprint"></i> ID: <?php echo htmlspecialchars($stackedItem['item_id']); ?></span>
                                    <span><i class="fas fa-calendar-alt"></i> Zuletzt: <?php echo date('d.m.Y H:i', strtotime($stackedItem['last_purchase'])); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="card-actions-wrapper">
                            <?php if (!empty($stackedItem['active_codes'])): ?>
                                <div class="active-codes-list">
                                    <?php foreach ($stackedItem['active_codes'] as $codeData): ?>
                                        <div class="active-code-display">
                                            <div class="code-header">
                                                <span class="status-dot pulse"></span>
                                                Code aktiv
                                            </div>
                                            <div class="code-wrapper">
                                                <span class="code-text" id="code-<?php echo $codeData['id']; ?>"><?php echo htmlspecialchars($codeData['code']); ?></span>
                                                <button class="copy-trigger" onclick="copyCode('<?php echo $codeData['id']; ?>')" title="Kopieren">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                            <div class="code-footer">
                                                <i class="far fa-clock"></i> <span class="code-timer" data-expires="<?php echo $codeData['expires_at']; ?>">--:--:--</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($hasAvailable): ?>
                                <div class="generate-action-box <?php echo !empty($stackedItem['active_codes']) ? 'has-active' : ''; ?>">
                                    <button class="btn-generate-new" onclick="generateCode(<?php echo $stackedItem['available_instances'][0]; ?>)">
                                        <i class="fas fa-key"></i> Code generieren (<?php echo count($stackedItem['available_instances']); ?> verfügbar)
                                    </button>
                                    <span class="action-hint">24h Gültigkeit</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
function generateCode(id) {
    fetch('api/generate_inventory_code.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `inventory_id=${id}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showNotification('Fehler: ' + data.message, 'error');
        }
    });
}

function copyCode(id) {
    const code = document.getElementById(`code-${id}`).innerText;
    navigator.clipboard.writeText(code).then(() => {
        showNotification('Code kopiert!', 'success');
    });
}

function updateTimers() {
    const timers = document.querySelectorAll('.code-timer');
    const now = Math.floor(Date.now() / 1000);

    timers.forEach(timer => {
        const expires = parseInt(timer.getAttribute('data-expires'));
        const remaining = expires - now;

        if (remaining <= 0) {
            timer.innerHTML = '<span class="expired">Abgelaufen</span>';
        } else {
            const h = Math.floor(remaining / 3600);
            const m = Math.floor((remaining % 3600) / 60);
            const s = remaining % 60;
            timer.innerText = 
                `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
        }
    });
}

setInterval(updateTimers, 1000);
updateTimers();
</script>

<style>
.inventory-section {
    padding: 150px 20px 80px;
    max-width: 1200px;
    margin: 0 auto;
}

.container {
    max-width: 1000px;
    margin: 0 auto;
}

/* Alert Box */
.alert-box.error {
    background: rgba(231, 76, 60, 0.1);
    border: 1px solid rgba(231, 76, 60, 0.2);
    border-radius: 15px;
    padding: 25px;
    display: flex;
    gap: 20px;
    margin-bottom: 40px;
    align-items: center;
}
.alert-icon { font-size: 2.5rem; color: #e74c3c; }
.alert-content h4 { margin-bottom: 5px; color: #e74c3c; }
.btn-setup {
    display: inline-block;
    margin-top: 10px;
    background: #e74c3c;
    color: white;
    padding: 8px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
}

.inventory-header {
    text-align: center;
    margin-bottom: 60px;
}
.header-tag {
    color: var(--primary-purple);
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 2px;
}
.inventory-header h1 {
    font-size: 3.5rem;
    margin: 10px 0;
    font-weight: 800;
}
.inventory-header p {
    color: var(--text-muted);
    font-size: 1.1rem;
}

.inventory-grid {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.inventory-card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 24px;
    padding: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}
.inventory-card:hover {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(125, 64, 255, 0.3);
    transform: translateY(-2px);
}

.card-main {
    display: flex;
    align-items: center;
    gap: 25px;
    flex: 1;
}

.item-icon-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.item-amount-badge {
    position: absolute;
    top: -12px;
    right: -12px;
    background: var(--primary-purple);
    color: white;
    font-size: 0.7rem;
    font-weight: 800;
    padding: 4px 8px;
    border-radius: 8px;
    border: 2px solid #1a1a1a;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}

.item-visual {
    width: 70px;
    height: 70px;
    background: rgba(125, 64, 255, 0.1);
    border-radius: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

.card-actions-wrapper {
    min-width: 300px;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.active-codes-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.generate-action-box.has-active {
    border-top: 1px solid rgba(255,255,255,0.05);
    padding-top: 15px;
}
.item-visual i { font-size: 1.8rem; }
.item-visual.item { color: #3498db; background: rgba(52, 152, 219, 0.1); }
.item-visual.permission { color: #e74c3c; background: rgba(231, 76, 60, 0.1); }
.category-label { font-size: 0.55rem; text-transform: uppercase; font-weight: 800; opacity: 0.6; }

.item-info h3 { font-size: 1.3rem; margin-bottom: 5px; }
.item-info p { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 10px; max-width: 450px; line-height: 1.4; }
.item-meta { display: flex; gap: 15px; font-size: 0.75rem; opacity: 0.4; }
.item-meta span { display: flex; align-items: center; gap: 5px; }

.active-code-display {
    background: rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(125, 64, 255, 0.3);
    border-radius: 18px;
    padding: 12px;
    text-align: center;
}
.code-header { font-size: 0.65rem; text-transform: uppercase; font-weight: 800; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; gap: 6px; letter-spacing: 1px; }
.pulse { width: 6px; height: 6px; background: #2ecc71; border-radius: 50%; box-shadow: 0 0 8px #2ecc71; animation: blink 1.5s infinite; }
@keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }

.code-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    background: rgba(255,255,255,0.03);
    padding: 8px;
    border-radius: 12px;
    margin-bottom: 8px;
    border: 1px solid rgba(255,255,255,0.05);
}
.code-text { font-family: 'JetBrains Mono', monospace; font-size: 1.3rem; font-weight: 700; color: #fff; letter-spacing: 2px; }
.copy-trigger { background: none; border: none; color: #7d40ff; cursor: pointer; transition: 0.2s; font-size: 1.1rem; }
.copy-trigger:hover { transform: scale(1.2); color: #fff; }

.code-footer { font-size: 0.75rem; color: #ffd700; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 5px; }

.btn-generate-new {
    width: 100%;
    background: linear-gradient(135deg, #7d40ff 0%, #9d5cff 100%);
    color: white;
    border: none;
    padding: 14px;
    border-radius: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 4px 15px rgba(125, 64, 255, 0.3);
}
.btn-generate-new:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(125, 64, 255, 0.4); }
.action-hint { display: block; text-align: center; font-size: 0.65rem; opacity: 0.4; margin-top: 8px; text-transform: uppercase; font-weight: 700; }

.no-items-card {
    text-align: center;
    padding: 60px;
    background: rgba(255,255,255,0.01);
    border-radius: 30px;
    border: 2px dashed rgba(255,255,255,0.05);
}
.empty-icon { font-size: 3.5rem; opacity: 0.1; margin-bottom: 15px; }
.no-items-card h3 { margin-bottom: 10px; }
.no-items-card p { opacity: 0.5; margin-bottom: 25px; }

.expired { color: #e74c3c; }

@media (max-width: 850px) {
    .inventory-card { flex-direction: column; gap: 25px; text-align: center; }
    .card-main { flex-direction: column; }
    .item-meta { justify-content: center; }
    .card-actions-wrapper { width: 100%; min-width: 0; }
}
</style>

<?php include 'includes/footer.php'; ?>
