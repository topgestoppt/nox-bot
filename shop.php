<?php
include 'includes/header.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

if (!$isClanMember) {
    echo '
    <section class="shop-section">
        <div class="container">
            <div class="alert-box error">
                <div class="alert-icon"><i class="fas fa-lock"></i></div>
                <div class="alert-content">
                    <h4>Keine Berechtigung</h4>
                    <p>Der Punkte-Shop ist exklusiv für Clan-Mitglieder reserviert. Bitte tritt unserem Clan bei, um Zugriff auf den Shop zu erhalten.</p>
                    <a href="index.php" class="btn-buy-now" style="display: inline-block; text-decoration: none; margin-top: 15px;">Zurück zur Startseite</a>
                </div>
            </div>
        </div>
    </section>';
    include 'includes/footer.php';
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

<section class="shop-section">
    <div class="container">
        <?php if (isset($dbError)): ?>
            <div class="alert-box error">
                <div class="alert-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="alert-content">
                    <h4>Datenbank-Fehler</h4>
                    <p>Der Shop konnte nicht geladen werden. Bitte führe das Setup aus.</p>
                    <a href="api/shop_db_setup.php" class="btn-setup">Setup ausführen</a>
                </div>
            </div>
        <?php endif; ?>

        <div class="shop-hero">
            <div class="hero-text">
                <span class="header-tag">Marktplatz</span>
                <div class="hero-title-with-logo">
                    <img src="<?php echo htmlspecialchars($shopLogo); ?>" alt="Logo" class="shop-hero-logo">
                    <div>
                        <h1>Nox-Points Shop</h1>
                        <p>Investiere deine Nox-Points in wertvolle Items und exklusive Berechtigungen.</p>
                    </div>
                </div>
            </div>
            
            <div class="points-display-card">
                <div class="points-info">
                    <span class="label">Dein Guthaben</span>
                    <span class="value"><?php echo number_format($userPoints, 0, ',', '.'); ?> <small>Points</small></span>
                </div>
                <div class="points-icon">
                    <i class="fas fa-coins"></i>
                </div>
            </div>
        </div>

        <div class="shop-grid">
            <?php if (empty($products) && !isset($dbError)): ?>
                <div class="empty-shop">
                    <i class="fas fa-store-slash"></i>
                    <h3>Der Shop ist aktuell leer</h3>
                    <p>Schau später wieder vorbei, wir füllen die Regale bald auf!</p>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): 
                    $hasEnoughPoints = ($userPoints >= $product['cost']);
                ?>
                    <div class="shop-card <?php echo !$hasEnoughPoints ? 'insufficient-funds' : ''; ?>">
                        <div class="card-visual <?php echo $product['category']; ?>">
                            <?php if ($product['icon_url']): 
                                $iconSrc = (strpos($product['icon_url'], 'http') === 0) ? $product['icon_url'] : '/' . ltrim($product['icon_url'], '/');
                            ?>
                                <img src="<?php echo htmlspecialchars($iconSrc); ?>" alt="Icon" class="product-icon">
                            <?php else: ?>
                                <i class="fas <?php echo $product['category'] == 'item' ? 'fa-cube' : 'fa-shield-halved'; ?>"></i>
                            <?php endif; ?>
                            <span class="category-tag"><?php echo ucfirst($product['category']); ?></span>
                        </div>
                        
                        <div class="card-body">
                            <h3><?php echo htmlspecialchars($product['display_name']); ?></h3>
                            <p><?php echo htmlspecialchars($product['description']); ?></p>
                            
                            <div class="card-footer">
                                <div class="price">
                                    <span class="amount"><?php echo number_format($product['cost'], 0, ',', '.'); ?></span>
                                    <span class="currency">Points</span>
                                </div>
                                <button 
                                    class="btn-buy-now <?php echo !$hasEnoughPoints ? 'disabled' : ''; ?>" 
                                    onclick="<?php echo $hasEnoughPoints ? "buyItem({$product['id']}, '" . addslashes($product['display_name']) . "', {$product['cost']})" : ''; ?>"
                                    title="<?php echo !$hasEnoughPoints ? 'Nicht genug Punkte' : ''; ?>"
                                >
                                    <i class="fas <?php echo $hasEnoughPoints ? 'fa-shopping-cart' : 'fa-lock'; ?>"></i> 
                                    <?php echo $hasEnoughPoints ? 'Kaufen' : 'Gesperrt'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
async function buyItem(id, name, cost) {
    if (await showConfirm(`Möchtest du "${name}" für ${cost} Nox-Points kaufen?`)) {
        fetch('api/buy_item.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `product_id=${id}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showNotification('Kauf erfolgreich! Das Item befindet sich nun in deinem Inventar.', 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showNotification('Fehler: ' + data.message, 'error');
            }
        });
    }
}
</script>

<style>
.shop-section {
    padding: 150px 20px 80px;
    max-width: 1200px;
    margin: 0 auto;
}

.container {
    max-width: 1100px;
    margin: 0 auto;
}

/* Hero & Points Display */
.shop-hero {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 60px;
    gap: 30px;
}

.header-tag {
    color: var(--primary-purple);
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 2px;
}

.hero-text h1 {
    font-size: 3.5rem;
    font-weight: 800;
    margin: 5px 0;
}

.hero-title-with-logo {
    display: flex;
    align-items: center;
    gap: 25px;
    margin-top: 10px;
}

.shop-hero-logo {
    width: 100px;
    height: 100px;
    object-fit: contain;
    filter: drop-shadow(0 0 15px rgba(125, 64, 255, 0.4));
}

.hero-text p {
    color: var(--text-muted);
    font-size: 1.1rem;
    max-width: 600px;
}

.points-display-card {
    background: linear-gradient(135deg, rgba(125, 64, 255, 0.2) 0%, rgba(125, 64, 255, 0.05) 100%);
    border: 1px solid rgba(125, 64, 255, 0.3);
    padding: 25px 35px;
    border-radius: 24px;
    display: flex;
    align-items: center;
    gap: 30px;
    backdrop-filter: blur(10px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.points-info {
    display: flex;
    flex-direction: column;
}

.points-info .label {
    font-size: 0.75rem;
    text-transform: uppercase;
    font-weight: 700;
    opacity: 0.6;
    letter-spacing: 1px;
}

.points-info .value {
    font-size: 2.2rem;
    font-weight: 800;
    color: #fff;
}

.points-info .value small {
    font-size: 1rem;
    color: #ffd700;
}

.points-icon {
    font-size: 2.5rem;
    color: #ffd700;
    filter: drop-shadow(0 0 10px rgba(255, 215, 0, 0.3));
}

/* Shop Grid */
.shop-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 30px;
}

.shop-card {
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 28px;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    display: flex;
    flex-direction: column;
}

.shop-card:hover {
    transform: translateY(-10px);
    background: rgba(255, 255, 255, 0.04);
    border-color: rgba(125, 64, 255, 0.4);
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
}

.card-visual {
    height: 160px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    position: relative;
    background: rgba(255,255,255,0.02);
}

.card-visual i {
    font-size: 3.5rem;
    transition: 0.4s;
}

.shop-card:hover .card-visual i {
    transform: scale(1.1) rotate(5deg);
}

.card-visual.item i { color: #3498db; }
.card-visual.permission i { color: #e74c3c; }

.product-icon {
    width: 100px;
    height: 100px;
    object-fit: contain;
    filter: drop-shadow(0 0 10px rgba(125, 64, 255, 0.3));
}

.category-tag {
    position: absolute;
    top: 20px;
    right: 20px;
    background: rgba(0,0,0,0.3);
    padding: 5px 12px;
    border-radius: 10px;
    font-size: 0.65rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    border: 1px solid rgba(255,255,255,0.1);
}

.card-body {
    padding: 30px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.card-body h3 {
    font-size: 1.5rem;
    margin-bottom: 12px;
    font-weight: 700;
}

.card-body p {
    color: var(--text-muted);
    font-size: 0.95rem;
    line-height: 1.5;
    margin-bottom: 25px;
    flex: 1;
}

.card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 20px;
    border-top: 1px solid rgba(255,255,255,0.05);
}

.price {
    display: flex;
    flex-direction: column;
}

.price .amount {
    font-size: 1.6rem;
    font-weight: 800;
    color: #ffd700;
    line-height: 1;
}

.price .currency {
    font-size: 0.7rem;
    text-transform: uppercase;
    font-weight: 700;
    opacity: 0.5;
    letter-spacing: 1px;
}

.btn-buy-now {
    background: linear-gradient(135deg, #7d40ff 0%, #9d5cff 100%);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: 0.3s;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 4px 15px rgba(125, 64, 255, 0.3);
}

.btn-buy-now:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(125, 64, 255, 0.5);
}

/* Insufficient Funds / Locked State */
.shop-card.insufficient-funds .card-visual {
    filter: grayscale(1) opacity(0.5);
}

.btn-buy-now.disabled {
    background: #333;
    color: #777;
    cursor: not-allowed;
    box-shadow: none;
    filter: blur(0.5px);
    border: 1px solid rgba(255,255,255,0.05);
}

.btn-buy-now.disabled:hover {
    transform: none;
    background: #333;
}

.btn-buy-now.disabled:hover::after {
    content: attr(title);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #e74c3c;
    color: white;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 0.7rem;
    white-space: nowrap;
    margin-bottom: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

/* Empty State */
.empty-shop {
    grid-column: 1 / -1;
    text-align: center;
    padding: 100px;
    background: rgba(255,255,255,0.01);
    border-radius: 30px;
    border: 2px dashed rgba(255,255,255,0.05);
}

.empty-shop i {
    font-size: 4rem;
    opacity: 0.1;
    margin-bottom: 20px;
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

@media (max-width: 900px) {
    .shop-hero { flex-direction: column; text-align: center; }
    .hero-text p { margin: 0 auto; }
    .points-display-card { width: 100%; justify-content: center; }
}
</style>

<?php include 'includes/footer.php'; ?>
