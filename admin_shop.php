<?php
include 'includes/header.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !($webPermission === 'Administrator' || $webPermission === 'GW-Manager')) {
    header('Location: index.php');
    exit;
}

// Fetch all products
try {
    $stmt = $pdo->query("SELECT * FROM shop_products ORDER BY cost ASC");
    $products = $stmt->fetchAll();
    
    // Fetch shop logo from settings
    $stmt = $pdo->query("SELECT setting_value FROM shop_settings WHERE setting_key = 'shop_logo'");
    $shopLogo = $stmt->fetchColumn() ?: 'assets/img/logo_mitte.png';
} catch (Exception $e) {
    $products = [];
    $dbError = $e->getMessage();
}
?>

<section class="dashboard-section admin-page">
    <div class="dashboard-container">
        <?php if (isset($dbError)): ?>
            <div class="alert alert-danger" style="background: rgba(231, 76, 60, 0.1); color: #e74c3c; padding: 15px; border-radius: 8px; margin: 20px; border: 1px solid #e74c3c;">
                <i class="fas fa-exclamation-triangle"></i> <strong>Datenbank-Fehler:</strong> <?php echo htmlspecialchars($dbError); ?>
                <br><small>Bitte führe die <a href="api/shop_db_setup.php" style="color: #e74c3c; text-decoration: underline;">Datenbank-Migration</a> aus.</small>
            </div>
        <?php endif; ?>
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
                        <a href="admin_shop.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_shop.php' ? 'active' : ''; ?>"><i class="fas fa-shopping-cart"></i> Punkte-Shop</a>
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
                <h1 class="glow-text">Punkte-Shop Verwaltung</h1>
                <p>Erstelle und verwalte Produkte für den Nox-Points Shop.</p>
            </div>

            <!-- Stats & Quick Actions Bar -->
            <div class="admin-stats-grid">
                <div class="admin-stat-card">
                    <div class="stat-icon"><i class="fas fa-box"></i></div>
                    <div class="stat-info">
                        <span class="stat-value"><?php echo count($products); ?></span>
                        <span class="stat-label">Produkte</span>
                    </div>
                </div>
                <div class="admin-stat-card">
                    <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                    <div class="stat-info">
                        <span class="stat-value"><?php 
                            try {
                                $stmt = $pdo->query("SELECT COUNT(*) FROM user_inventory");
                                echo $stmt->fetchColumn();
                            } catch (Exception $e) { echo "0"; }
                        ?></span>
                        <span class="stat-label">Verkäufe gesamt</span>
                    </div>
                </div>
                <div class="admin-quick-actions">
                    <button class="btn-quick action-add" onclick="openProductModal()">
                        <i class="fas fa-plus-circle"></i> Produkt hinzufügen
                    </button>
                    <button class="btn-quick action-sync" onclick="location.reload()">
                        <i class="fas fa-sync"></i> Aktualisieren
                    </button>
                    <button class="btn-quick action-settings" onclick="openSettingsModal()">
                        <i class="fas fa-cog"></i> Einstellungen
                    </button>
                    <a href="shop.php" class="btn-quick action-view" target="_blank">
                        <i class="fas fa-external-link-alt"></i> Shop-Vorschau
                    </a>
                </div>
            </div>

            <div class="admin-content-grid">
                <div class="content-box main-box">
                    <div class="box-header">
                        <div class="header-left">
                            <h3><i class="fas fa-shopping-bag"></i> Produktliste</h3>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Icon</th>
                                    <th>Produkt</th>
                                    <th>Kosten</th>
                                    <th>Kategorie</th>
                                    <th class="text-right">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Keine Produkte gefunden.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                                <div class="product-icon-preview">
                                                    <?php if ($product['icon_url']): 
                                                        $iconSrc = (strpos($product['icon_url'], 'http') === 0) ? $product['icon_url'] : '/' . ltrim($product['icon_url'], '/');
                                                    ?>
                                                        <img src="<?php echo htmlspecialchars($iconSrc); ?>" alt="Icon">
                                                    <?php else: ?>
                                                        <div class="icon-fallback"><i class="fas <?php echo $product['category'] == 'item' ? 'fa-cube' : 'fa-shield-halved'; ?>"></i></div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="product-info-cell">
                                                    <span class="product-name"><?php echo htmlspecialchars($product['display_name']); ?></span>
                                                    <code class="product-item-id"><?php echo htmlspecialchars($product['item_id']); ?></code>
                                                </div>
                                            </td>
                                            <td><span class="points-val"><?php echo number_format($product['cost'], 0, ',', '.'); ?> <small>Points</small></span></td>
                                            <td>
                                                <span class="category-badge <?php echo $product['category']; ?>">
                                                    <?php echo ucfirst($product['category']); ?>
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <div class="action-buttons">
                                                    <button class="btn-action btn-points" onclick="openProductModal(<?php echo htmlspecialchars(json_encode($product)); ?>)" title="Bearbeiten">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn-action btn-remove" onclick="deleteProduct(<?php echo $product['id']; ?>)" title="Löschen">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="content-box side-box">
                    <div class="box-header">
                        <h3><i class="fas fa-history"></i> Letzte Verkäufe</h3>
                    </div>
                    <div class="recent-sales-list">
                        <?php
                        try {
                            $stmt = $pdo->query("
                                SELECT ui.*, sp.display_name, u.username, u.avatar 
                                FROM user_inventory ui
                                JOIN shop_products sp ON ui.product_id = sp.id
                                JOIN users u ON ui.discord_id = u.discord_id
                                ORDER BY ui.purchased_at DESC
                                LIMIT 5
                            ");
                            $sales = $stmt->fetchAll();
                            
                            if (empty($sales)):
                                echo '<div class="empty-side-state">Keine Verkäufe bisher.</div>';
                            else:
                                foreach ($sales as $sale):
                                ?>
                                <div class="sale-item">
                                    <img src="<?php echo $sale['avatar'] ? "https://cdn.discordapp.com/avatars/{$sale['discord_id']}/{$sale['avatar']}.png" : "assets/img/default_avatar.png"; ?>" alt="User" class="sale-avatar">
                                    <div class="sale-info">
                                        <span class="sale-user"><?php echo htmlspecialchars($sale['username']); ?></span>
                                        <span class="sale-product"><?php echo htmlspecialchars($sale['display_name']); ?></span>
                                        <span class="sale-date"><?php echo date('d.m. H:i', strtotime($sale['purchased_at'])); ?></span>
                                    </div>
                                </div>
                                <?php
                                endforeach;
                            endif;
                        } catch (Exception $e) {
                            echo '<div class="empty-side-state">Fehler beim Laden.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</section>

<!-- Settings Modal -->
<div id="settingsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-cog"></i> Shop-Einstellungen</h3>
            <span class="close" onclick="closeModal('settingsModal')">&times;</span>
        </div>
        <form id="settingsForm">
            <div class="form-group">
                <label for="shop_logo_url">Shop Logo URL</label>
                <input type="url" name="shop_logo" id="shop_logo_url" class="form-control" value="<?php echo htmlspecialchars($shopLogo); ?>" required>
                <small style="color: #888; margin-top: 5px; display: block;">Die URL zum Hauptlogo des Shops (oben im Hero-Bereich).</small>
            </div>
            <div class="logo-preview-box" style="margin: 15px 0; padding: 15px; background: rgba(0,0,0,0.2); border-radius: 8px; text-align: center;">
                <img id="logo-preview" src="<?php echo htmlspecialchars($shopLogo); ?>" style="max-height: 80px; object-fit: contain;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('settingsModal')">Abbrechen</button>
                <button type="submit" class="btn-primary">Einstellungen speichern</button>
            </div>
        </form>
    </div>
</div>

<!-- Product Modal -->
<div id="productModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle"><i class="fas fa-plus"></i> Produkt hinzufügen</h3>
            <span class="close" onclick="closeModal('productModal')">&times;</span>
        </div>
        <form id="productForm" enctype="multipart/form-data">
            <input type="hidden" name="id" id="product_id">
            <div class="form-group">
                <label for="display_name">Anzeige-Name</label>
                <input type="text" name="display_name" id="display_name" class="form-control" required placeholder="z.B. 64x Diamanten">
            </div>
            <div class="form-group">
                <label for="icon_file">Produkt-Icon hochladen</label>
                <input type="file" name="icon_file" id="icon_file" class="form-control" accept="image/*">
                <small style="color: #888;">Empfohlen: Quadratisches Bild (PNG/JPG)</small>
            </div>
            <input type="hidden" name="icon_url" id="icon_url">
            <div class="form-group">
                <label for="item_id">ItemID</label>
                <input type="text" name="item_id" id="item_id" class="form-control" required placeholder="z.B. diamond">
            </div>
            <div class="form-row">
                <div class="form-group col-6">
                    <label for="cost">Kosten (Nox-Points)</label>
                    <input type="number" name="cost" id="cost" class="form-control" required min="0">
                </div>
                <div class="form-group col-6">
                    <label for="category">Kategorie</label>
                    <select name="category" id="category" class="form-control" required>
                        <option value="item">Item</option>
                        <option value="permission">Permission</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="description">Beschreibung</label>
                <textarea name="description" id="description" class="form-control" rows="3" required placeholder="Kurze Beschreibung des Produkts"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('productModal')">Abbrechen</button>
                <button type="submit" class="btn-primary">Speichern</button>
            </div>
        </form>
    </div>
</div>



<script>
function openProductModal(product = null) {
    const modal = document.getElementById('productModal');
    const form = document.getElementById('productForm');
    const title = document.getElementById('modalTitle');
    
    if (product) {
        title.innerHTML = '<i class="fas fa-edit"></i> Produkt bearbeiten';
        document.getElementById('product_id').value = product.id;
        document.getElementById('display_name').value = product.display_name;
        document.getElementById('icon_url').value = product.icon_url || '';
        document.getElementById('item_id').value = product.item_id;
        document.getElementById('cost').value = product.cost;
        document.getElementById('category').value = product.category;
        document.getElementById('description').value = product.description;
    } else {
        title.innerHTML = '<i class="fas fa-plus"></i> Produkt hinzufügen';
        form.reset();
        document.getElementById('product_id').value = '';
    }
    
    modal.style.display = 'block';
    document.body.classList.add('modal-open');
}

function openSettingsModal() {
    document.getElementById('settingsModal').style.display = 'block';
    document.body.classList.add('modal-open');
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
    
    // Nur entfernen wenn kein anderes Modal mehr offen ist
    const openModals = document.querySelectorAll('.modal[style*="display: block"]');
    if (openModals.length === 0) {
        document.body.classList.remove('modal-open');
    }
}

async function deleteProduct(id) {
    if (await showConfirm('Bist du sicher, dass du dieses Produkt löschen möchtest?')) {
        fetch('api/shop_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=delete_product&id=${id}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) location.reload();
            else showNotification('Fehler: ' + data.message, 'error');
        });
    }
}

document.getElementById('productForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const id = formData.get('id');
    formData.append('action', id ? 'edit_product' : 'add_product');
    
    fetch('api/shop_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) location.reload();
        else showNotification('Fehler: ' + data.message, 'error');
    });
};

document.getElementById('settingsForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'update_settings');
    
    fetch('api/shop_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showNotification('Einstellungen gespeichert!', 'success');
            setTimeout(() => location.reload(), 2000);
        } else showNotification('Fehler: ' + data.message, 'error');
    });
};

document.getElementById('shop_logo_url').oninput = function() {
    document.getElementById('logo-preview').src = this.value;
};

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
        event.target.style.display = "none";
        
        // Check if any other modal is still open
        const openModals = document.querySelectorAll('.modal[style*="display: block"]');
        if (openModals.length === 0) {
            document.body.classList.remove('modal-open');
        }
    }
}
</script>

<style>
/* Admin Stats Grid */
.admin-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.admin-stat-card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.admin-stat-card .stat-icon {
    width: 50px;
    height: 50px;
    background: rgba(176, 39, 245, 0.1);
    color: #B027F5;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.admin-stat-card .stat-info {
    display: flex;
    flex-direction: column;
}

.admin-stat-card .stat-value {
    font-size: 1.4rem;
    font-weight: 800;
    color: #fff;
    line-height: 1.2;
}

.admin-stat-card .stat-label {
    font-size: 0.75rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.admin-quick-actions {
    display: flex;
    gap: 12px;
    align-items: stretch;
    justify-content: flex-end;
}

.btn-quick {
    padding: 10px 15px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.05);
    background: rgba(255,255,255,0.02);
    color: #fff;
    font-weight: 600;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 5px;
    text-decoration: none;
    min-width: 100px;
    text-align: center;
}

.btn-quick i {
    font-size: 1.2rem;
}

.btn-quick:hover {
    background: rgba(255,255,255,0.05);
    transform: translateY(-2px);
}

.action-add {
    background: linear-gradient(135deg, #7d40ff 0%, #9d5cff 100%);
    border: none;
    box-shadow: 0 4px 15px rgba(125, 64, 255, 0.2);
}

.action-add:hover {
    background: linear-gradient(135deg, #8e5cff 0%, #ad7aff 100%);
    box-shadow: 0 6px 20px rgba(125, 64, 255, 0.4);
}

.action-sync i { color: #3498db; }
.action-settings i { color: #f39c12; }
.action-view i { color: #2ecc71; }

/* Admin Content Grid */
.admin-content-grid {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 30px;
    align-items: start;
}

@media (max-width: 1100px) {
    .admin-content-grid {
        grid-template-columns: 1fr;
    }
}

.product-item-id {
    font-size: 0.7rem;
    background: rgba(255,255,255,0.05);
    padding: 2px 6px;
    border-radius: 4px;
    color: #aaa;
    margin-top: 4px;
    display: inline-block;
}

.recent-sales-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.sale-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    background: rgba(255,255,255,0.02);
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.03);
}

.sale-avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
}

.sale-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.sale-user {
    font-size: 0.85rem;
    font-weight: 700;
}

.sale-product {
    font-size: 0.75rem;
    color: #B027F5;
}

.sale-date {
    font-size: 0.65rem;
    color: var(--text-muted);
}

.empty-side-state {
    padding: 30px;
    text-align: center;
    color: var(--text-muted);
    font-style: italic;
    font-size: 0.9rem;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table th {
    text-align: left;
    padding: 15px;
    border-bottom: 2px solid rgba(255,255,255,0.05);
    color: rgba(255,255,255,0.6);
    font-size: 0.8rem;
    text-transform: uppercase;
}

.admin-table td {
    padding: 15px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    vertical-align: middle;
}

.product-info-cell {
    display: flex;
    flex-direction: column;
}

.action-buttons {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}

.btn-action {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.1);
    background: rgba(255,255,255,0.05);
    color: white;
    cursor: pointer;
    transition: 0.2s;
}

.btn-action:hover {
    background: rgba(255,255,255,0.1);
    transform: scale(1.1);
}

.btn-remove:hover { color: #e74c3c; border-color: #e74c3c; }
.btn-points:hover { color: #3498db; border-color: #3498db; }

.product-icon-preview {
    width: 45px;
    height: 45px;
    background: rgba(255,255,255,0.05);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.1);
}
.product-icon-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.icon-fallback {
    font-size: 1.2rem;
    opacity: 0.5;
}
.product-info-cell {
    display: flex;
    flex-direction: column;
}
.product-name {
    font-weight: 700;
    color: #fff;
}
.product-desc {
    font-size: 0.75rem;
    color: var(--text-muted);
    max-width: 250px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.category-badge {
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
}
.category-badge.item { background: rgba(52, 152, 219, 0.1); color: #3498db; }
.category-badge.permission { background: rgba(231, 76, 60, 0.1); color: #e74c3c; }

.points-val {
    color: #ffd700;
    font-weight: 700;
}
.points-val small { font-size: 0.7rem; opacity: 0.6; }
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.7);
    backdrop-filter: blur(5px);
}
.modal-content {
    background: #1a1a2e;
    margin: 5% auto;
    padding: 25px;
    border: 1px solid rgba(255,255,255,0.1);
    width: 500px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}
.close:hover {
    color: white;
}
.form-group {
    margin-bottom: 15px;
}
.form-row {
    display: flex;
    gap: 15px;
}
.col-6 {
    flex: 1;
}
.form-control {
    width: 100%;
    padding: 10px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 6px;
    color: white;
    box-sizing: border-box;
}

/* Fix for select options visibility on dark background */
select.form-control option {
    background-color: #1a1a2e;
    color: white;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}
</style>

<?php include 'includes/footer.php'; ?>
