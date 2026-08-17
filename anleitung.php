<?php
include 'includes/header.php';

// Initialize variable if not set in header
if (!isset($userJoinedAt)) {
    $userJoinedAt = null;
}

// Check login
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

// Check if user is new (joined within last 24 hours)
$isNewMember = false;
$hoursLeft = 0;

if ($userJoinedAt) {
    $joinTimestamp = strtotime($userJoinedAt);
    $currentTime = time();
    $diffSeconds = $currentTime - $joinTimestamp;
    $diffHours = $diffSeconds / 3600;
    
    if ($diffHours <= 24) {
        $isNewMember = true;
        $hoursLeft = max(0, 24 - $diffHours);
    }
} else {
    // If joined_at is null, assume they are new for now
    $isNewMember = true;
    $hoursLeft = 24;
}

// Admin bypass and flag if access expired
$accessExpired = (!$isNewMember && !$isAdmin);

// Ensure columns exist (XP/Level migration check if not done by dashboard yet)
try {
    $pdo->query("SELECT xp, level FROM users LIMIT 1");
} catch (Exception $e) {
    try { $pdo->exec("ALTER TABLE users ADD COLUMN xp FLOAT DEFAULT 0, ADD COLUMN level INT DEFAULT 0"); } catch (Exception $ex) {}
}

// Fetch additional data
$stmt = $pdo->prepare("SELECT points, level, xp FROM users WHERE discord_id = ?");
$stmt->execute([$_SESSION['user']['id']]);
$userData = $stmt->fetch();

$points = $userData['points'] ?? 0;
$level = $userData['level'] ?? 0;

// Reward Status Check
$stmtReward = $pdo->prepare("SELECT last_claim FROM user_rewards WHERE discord_id = ?");
$stmtReward->execute([$_SESSION['user']['id']]);
$lastClaimDate = $stmtReward->fetchColumn();
$hasRewardPending = ($lastClaimDate !== date('Y-m-d'));

?>

<style>
    .pixel-title-rules {
        font-family: 'Inter', sans-serif;
        font-size: 4rem;
        font-weight: 900;
        margin-bottom: 20px;
        background: linear-gradient(to bottom, #fff 0%, #7d40ff 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        filter: drop-shadow(0 0 15px rgba(125, 64, 255, 0.4));
        text-transform: uppercase;
    }

    .rules-subtitle-hero {
        font-size: 1.2rem;
        color: var(--text-muted);
        margin-bottom: 40px;
    }

    .guide-hero {
        padding: 160px 0 80px;
        background: radial-gradient(circle at center, rgba(125, 64, 255, 0.15) 0%, rgba(5, 5, 16, 0) 70%);
        text-align: center;
        position: relative;
    }

    .guide-container {
        max-width: 1100px;
        margin: -40px auto 100px;
        padding: 0 20px;
    }

    .welcome-card {
        background: rgba(20, 20, 35, 0.6);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(125, 64, 255, 0.2);
        border-radius: 24px;
        padding: 40px;
        margin-bottom: 40px;
        position: relative;
        overflow: hidden;
        animation: fadeInDown 0.8s ease-out;
    }

    .welcome-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, #7d40ff, #a855f7);
    }

    .timer-badge {
        position: absolute;
        top: 20px;
        right: 20px;
        background: rgba(255, 50, 50, 0.1);
        color: #ff4d4d;
        border: 1px solid rgba(255, 77, 77, 0.2);
        padding: 8px 16px;
        border-radius: 12px;
        font-size: 0.9rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
        animation: pulse 2s infinite;
    }

    .guide-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
    }

    @media (max-width: 992px) {
        .guide-grid {
            grid-template-columns: 1fr;
        }
    }

    .checklist-card {
        background: rgba(20, 20, 35, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 24px;
        padding: 30px;
        height: 100%;
    }

    .checklist-item {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 20px;
        background: rgba(255, 255, 255, 0.02);
        border-radius: 16px;
        margin-bottom: 15px;
        transition: all 0.3s ease;
        border: 1px solid transparent;
        text-decoration: none;
        color: inherit;
        animation: fadeInUp 0.5s ease-out both;
    }

    .checklist-item:nth-child(2) { animation-delay: 0.1s; }
    .checklist-item:nth-child(3) { animation-delay: 0.2s; }
    .checklist-item:nth-child(4) { animation-delay: 0.3s; }
    .checklist-item:nth-child(5) { animation-delay: 0.4s; }

    .checklist-item:hover {
        background: rgba(255, 255, 255, 0.05);
        transform: translateX(10px);
        border-color: rgba(125, 64, 255, 0.3);
    }

    .checklist-item.completed {
        border-left: 4px solid #27F570;
    }

    .check-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .check-icon.pending {
        background: rgba(255, 255, 255, 0.05);
        color: var(--text-muted);
    }

    .check-icon.done {
        background: rgba(39, 245, 112, 0.1);
        color: #27F570;
    }

    .check-content h4 {
        margin: 0;
        font-size: 1.1rem;
        color: #fff;
    }

    .check-content p {
        margin: 5px 0 0;
        font-size: 0.9rem;
        color: var(--text-muted);
    }

    .info-sidebar {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .info-card {
        background: rgba(20, 20, 35, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 24px;
        padding: 25px;
        transition: transform 0.3s ease;
    }

    .info-card:hover {
        transform: translateY(-5px);
    }

    .points-badge {
        background: linear-gradient(135deg, #7d40ff, #a855f7);
        padding: 15px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
    }

    .points-badge i {
        font-size: 2rem;
    }

    .points-info h3 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 800;
    }

    .points-info span {
        font-size: 0.8rem;
        opacity: 0.8;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .mentor-card {
        background: linear-gradient(rgba(20, 20, 35, 0.8), rgba(20, 20, 35, 0.8)), 
                    url('https://i.imgur.com/3Z4S8Xn.png') center/cover;
        border: 1px solid rgba(125, 64, 255, 0.3);
    }

    .tag-coming-soon {
        background: rgba(255, 255, 255, 0.1);
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.7rem;
        vertical-align: middle;
        margin-left: 10px;
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.6; }
        100% { opacity: 1; }
    }

    .glow-text {
        text-shadow: 0 0 20px rgba(125, 64, 255, 0.5);
    }
</style>

<section class="guide-hero">
    <div class="container">
        <h1 class="pixel-title-rules glow-text">Willkommen Neuling!</h1>
        <p class="rules-subtitle-hero">Hier ist dein persönlicher Guide für einen perfekten Start im Noxus.</p>
    </div>
</section>

<div class="guide-container" data-reveal>
    <?php if ($accessExpired): ?>
        <div class="welcome-card" style="text-align: center; border-color: #ff4d4d; background: rgba(255, 50, 50, 0.05);">
            <div class="timer-badge" style="position: relative; top: 0; right: 0; display: inline-flex; margin-bottom: 20px;">
                <i class="fas fa-exclamation-triangle"></i> Zugriff abgelaufen
            </div>
            <h2 style="color: #ff4d4d; margin-bottom: 15px;">Zeit abgelaufen!</h2>
            <p style="color: var(--text-muted); font-size: 1.1rem; max-width: 800px; margin: 0 auto;">
                Dein 24-Stunden-Fenster für die Einweisungsseite ist leider abgelaufen. Du findest nun alle wichtigen Informationen in deinem Dashboard oder im Discord.
            </p>
            <div style="margin-top: 30px;">
                <a href="dashboard.php" class="btn-minecraft" style="padding: 12px 25px;">
                    <span class="btn-text">Zum Dashboard</span>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="welcome-card" data-reveal>
            <?php if ($isNewMember): ?>
                <div class="timer-badge">
                    <i class="fas fa-clock"></i> Noch <?php echo round($hoursLeft, 1); ?>h Zugriff
                </div>
            <?php else: ?>
                <div class="timer-badge" style="background: rgba(125, 64, 255, 0.1); color: #7d40ff; border-color: rgba(125, 64, 255, 0.2);">
                    <i class="fas fa-user-shield"></i> Admin-Ansicht
                </div>
            <?php endif; ?>
            <h2 style="color: #7d40ff; margin-bottom: 15px;">Schön, dass du da bist, <?php echo htmlspecialchars($_SESSION['user']['username']); ?>!</h2>
            <p style="color: var(--text-muted); font-size: 1.1rem; max-width: 800px;">
                Wir freuen uns riesig, dich in unserer Gemeinschaft begrüßen zu dürfen. Damit du dich schnell zurechtfindest, haben wir dir hier die wichtigsten Schritte zusammengefasst. Du hast 24 Stunden Zeit, diese Seite zu nutzen.
            </p>
        </div>

        <div class="guide-grid" data-reveal>
            <div class="checklist-column">
                <div class="checklist-card">
                    <h3 style="margin-bottom: 25px; display: flex; align-items: center; gap: 15px;">
                        <i class="fas fa-list-check" style="color: #7d40ff;"></i> Deine Start-Checkliste
                    </h3>

                    <a href="rules.php" class="checklist-item">
                        <div class="check-icon pending"><i class="fas fa-gavel"></i></div>
                        <div class="check-content">
                            <h4>Regeln durchlesen</h4>
                            <p>Mache dich mit den Clan- und Verleih-Regeln vertraut.</p>
                        </div>
                    </a>

                    <div class="checklist-item" style="opacity: 0.7; cursor: not_allowed;">
                        <div class="check-icon pending"><i class="fas fa-user-tag"></i></div>
                        <div class="check-content">
                            <h4>Selfroles wählen <span class="tag-coming-soon">COMING SOON</span></h4>
                            <p>Wähle bald deine Rollen (Alter, Spiele, Plattform) direkt hier.</p>
                        </div>
                    </div>

                    <a href="rewards.php" class="checklist-item <?php echo !$hasRewardPending ? 'completed' : ''; ?>">
                        <div class="check-icon <?php echo !$hasRewardPending ? 'done' : 'pending'; ?>">
                            <i class="<?php echo !$hasRewardPending ? 'fas fa-check' : 'fas fa-gift'; ?>"></i>
                        </div>
                        <div class="check-content">
                            <h4>Tägliche Belohnung</h4>
                            <p><?php echo !$hasRewardPending ? 'Du hast deine Belohnung bereits abgeholt!' : 'Hol dir deine ersten Nox-Points ab.'; ?></p>
                        </div>
                    </a>

                    <a href="shop.php" class="checklist-item">
                        <div class="check-icon pending"><i class="fas fa-shop"></i></div>
                        <div class="check-content">
                            <h4>Punkte-Shop abchecken</h4>
                            <p>Schau dir an, was du dir alles für Nox-Points kaufen kannst.</p>
                        </div>
                    </a>
                </div>
            </div>

            <div class="info-sidebar">
                <div class="info-card">
                    <div class="points-badge">
                        <i class="fas fa-coins"></i>
                        <div class="points-info">
                            <span>Dein Kontostand</span>
                            <h3><?php echo number_format($points, 0, ',', '.'); ?> <small>Points</small></h3>
                        </div>
                    </div>
                    <p style="font-size: 0.9rem; color: var(--text-muted);">
                        <strong>Tipp:</strong> Du hast zum Start <strong>1.000 Nox-Points</strong> geschenkt bekommen! Nutze sie weise im Shop oder spare auf exklusive Items.
                    </p>
                </div>

                <div class="info-card">
                    <h4 style="color: #fff; margin-bottom: 15px;"><i class="fas fa-trophy" style="color: #ffd700;"></i> Dein Rang</h4>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="status-tag" style="background: rgba(125, 64, 255, 0.1); color: #7d40ff; border: 1px solid rgba(125, 64, 255, 0.2); padding: 5px 12px; border-radius: 8px;">
                            <?php echo htmlspecialchars($highestRole ?? 'Schattenkind'); ?>
                        </span>
                        <span style="color: var(--text-muted); font-size: 0.9rem;">Level <?php echo $level; ?></span>
                    </div>
                </div>

                <div class="info-card mentor-card">
                    <h4 style="color: #fff; margin-bottom: 10px;">Werde Teil des Teams!</h4>
                    <p style="font-size: 0.85rem; color: rgba(255,255,255,0.8); margin-bottom: 15px;">
                        Du möchtest mehr Verantwortung? Bewirb dich als <strong>Mentor (Supporter)</strong>, <strong>Farmer</strong> oder <strong>Architekt</strong>!
                    </p>
                    <a href="https://discord.com/channels/1499865431064711365/..." class="btn-minecraft" style="padding: 10px 15px; font-size: 0.8rem; width: 100%; text-align: center;">
                        <span class="btn-text">Ticket eröffnen</span>
                    </a>
                    <p style="font-size: 0.7rem; color: rgba(255,255,255,0.5); margin-top: 10px; text-align: center;">
                        Bewerbungen über das Discord Ticket-System.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
