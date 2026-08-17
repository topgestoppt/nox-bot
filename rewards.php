<?php
include 'includes/header.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

$discord_id = $_SESSION['user']['id'];
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$canClaim = true; // Default to true
$streakCount = 0;
$lastClaim = null;

try {
    $stmt = $pdo->prepare("SELECT last_claim, streak_count FROM user_rewards WHERE discord_id = ?");
    $stmt->execute([$discord_id]);
    $rewardData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($rewardData) {
        $lastClaim = $rewardData['last_claim'];
        $streakCount = (int)$rewardData['streak_count'];
        
        if ($lastClaim === $today) {
            $canClaim = false;
        }
        
        // UI logic: Reset streak view if missed a day
        if ($lastClaim !== $today && $lastClaim !== $yesterday) {
            $streakCount = 0;
        }
    }
} catch (Exception $e) {
    // Falls Tabelle fehlt, bleibt canClaim = true
}

$milestones = [
    3 => 1000,
    7 => 2500,
    14 => 5500,
    21 => 8250,
    30 => 11500,
    90 => 35000,
    180 => 75000,
    365 => 175000,
    730 => 500000
];

// Determine next milestone
$nextMilestone = 3;
foreach(array_keys($milestones) as $m) {
    if ($streakCount < $m) {
        $nextMilestone = $m;
        break;
    }
}
$daysToNext = $nextMilestone - $streakCount;
?>

<section class="rewards-redesign">
    <div class="container">
        <div class="page-title-area">
            <h1><i class="fas fa-gift" style="color: var(--primary-purple);"></i> Belohnungen</h1>
            <p>Verdiene Nox-Points, indem du jeden Tag die Website besuchst. Jeder aufeinanderfolgende Tag erhöht deine Streak. Erreiche Meilensteine für Bonusbelohnungen.</p>
            <div class="streak-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Achtung: Du hast bis <b>23:59 Uhr (DE)</b> Zeit, deine Belohnung abzuholen. Danach verfällt deine Streak!</span>
            </div>
        </div>

        <div class="rewards-upper-grid">
            <!-- Streak Banner -->
            <div class="streak-banner-card">
                <div class="streak-number"><?php echo $streakCount; ?></div>
                <div class="streak-details">
                    <h3>Tage Streak</h3>
                    <p>Noch <?php echo $daysToNext; ?> Tage bis Tag <?php echo $nextMilestone; ?></p>
                    <span class="record-label">Rekord: <?php echo $streakCount; ?> Tage</span>
                </div>
                <div class="banner-bg-icon">
                    <i class="fas fa-fire"></i>
                </div>
            </div>

            <!-- Claim Box -->
            <div class="daily-claim-card">
                <div class="claim-header">
                    <i class="fas fa-gift" style="color: #ffa500;"></i>
                    <span>Tägliche Belohnung</span>
                </div>
                <p>Hol dir jeden Tag deine Belohnung ab. Jeder Login zählt für deine Streak.</p>
                <div class="claim-action-wrapper">
                    <?php if ($canClaim): ?>
                        <button id="claim-reward-btn" class="btn-laby-orange">
                            <i class="fas fa-hand-holding-usd"></i> Abholen
                        </button>
                    <?php else: ?>
                        <button class="btn-laby-disabled" disabled>
                            <i class="fas fa-check"></i> Bereits abgeholt
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Milestones List -->
        <div class="streak-rewards-container">
            <div class="streak-header-row">
                <h3><i class="fas fa-fire" style="color: #ff4500;"></i> Streak-Belohnungen</h3>
                <span class="info-hint">Boni werden automatisch beim täglichen Abholen gutgeschrieben.</span>
            </div>
            
            <div class="milestones-scroll-list">
                <?php foreach ($milestones as $days => $points): 
                    $isReached = ($streakCount >= $days);
                    $isNext = ($streakCount < $days && ($prevDays ?? 0) <= $streakCount);
                    $prevDays = $days;
                    
                    // Calculate progress percentage for this specific row
                    $rowProgress = 0;
                    if ($isReached) {
                        $rowProgress = 100;
                    } else {
                        $rowProgress = ($streakCount / $days) * 100;
                    }
                ?>
                    <div class="milestone-row <?php echo $isReached ? 'status-reached' : ($isNext ? 'status-next' : ''); ?>" 
                         style="--progress: <?php echo $rowProgress; ?>%">
                        <div class="row-progress-bg"></div>
                        <div class="milestone-day-col">
                            <span class="day-num"><?php echo $days; ?></span>
                            <span class="dot">•</span>
                            <span class="day-text">Tag <?php echo $days; ?></span>
                            <?php if ($isNext && $streakCount > 0): ?>
                                <span class="progress-tag"><?php echo $streakCount; ?>/<?php echo $days; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="milestone-reward-col">
                            <i class="fas fa-coins"></i> <?php echo number_format($points, 0, ',', '.'); ?>
                        </div>
                        <div class="milestone-status-col">
                            <?php if ($isReached): ?>
                                <span class="status-btn reached"><i class="fas fa-check"></i> Erreicht</span>
                            <?php elseif ($isNext): ?>
                                <span class="status-btn next">In Arbeit</span>
                            <?php else: ?>
                                <span class="status-btn locked"><i class="fas fa-lock"></i> Gesperrt</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const claimBtn = document.getElementById('claim-reward-btn');
    if (claimBtn) {
        claimBtn.addEventListener('click', () => {
            claimBtn.disabled = true;
            claimBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Wird abgeholt...';
            
            // Add animation to the card
            const card = document.querySelector('.daily-claim-card');
            if (card) card.classList.add('collecting-anim');

            fetch('api/claim_reward.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(data.message, 'error');
                        claimBtn.disabled = false;
                        claimBtn.innerHTML = '<i class="fas fa-hand-holding-usd"></i> Abholen';
                    }
                })
                .catch(err => {
                    showNotification('Fehler bei der Anfrage.', 'error');
                    claimBtn.disabled = false;
                });
        });
    }
});
</script>

<style>
.rewards-redesign {
    padding: 120px 20px 60px;
    max-width: 1100px;
    margin: 0 auto;
    color: #e0e0e0;
}

.page-title-area h1 {
    font-size: 2.2rem;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.page-title-area p {
    color: #888;
    max-width: 900px;
    line-height: 1.5;
    margin-bottom: 15px;
    font-size: 0.95rem;
}

.streak-warning {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    background: rgba(255, 165, 0, 0.1);
    border: 1px solid rgba(255, 165, 0, 0.2);
    padding: 10px 20px;
    border-radius: 12px;
    color: #ffa500;
    font-size: 0.85rem;
    margin-bottom: 40px;
    animation: fadeIn 0.8s ease-out;
}

.streak-warning i {
    animation: pulse 2s infinite;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

.rewards-upper-grid {
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 25px;
    margin-bottom: 40px;
}

/* Streak Banner */
.streak-banner-card {
    background: linear-gradient(135deg, rgba(30, 30, 40, 0.8) 0%, rgba(20, 20, 25, 0.9) 100%);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 20px;
    padding: 30px;
    display: flex;
    align-items: center;
    gap: 30px;
    position: relative;
    overflow: hidden;
    min-height: 180px;
}

.streak-number {
    font-size: 6rem;
    font-weight: 900;
    color: #ffa500;
    line-height: 1;
    text-shadow: 0 0 30px rgba(255, 165, 0, 0.3);
    z-index: 2;
}

.streak-details h3 {
    font-size: 1.5rem;
    margin-bottom: 5px;
    z-index: 2;
    position: relative;
}

.streak-details p {
    color: #888;
    font-size: 0.9rem;
    margin-bottom: 10px;
    z-index: 2;
    position: relative;
}

.record-label {
    font-size: 0.8rem;
    color: #555;
    z-index: 2;
    position: relative;
}

.banner-bg-icon {
    position: absolute;
    right: -20px;
    bottom: -30px;
    font-size: 12rem;
    color: rgba(255, 69, 0, 0.05);
    transform: rotate(-15deg);
}

/* Claim Card */
.daily-claim-card {
    background: rgba(25, 25, 30, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 20px;
    padding: 30px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.claim-header {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 700;
    margin-bottom: 15px;
    font-size: 1.1rem;
}

.daily-claim-card p {
    color: #888;
    font-size: 0.9rem;
    margin-bottom: 25px;
    line-height: 1.4;
}

.btn-laby-orange {
    width: 100%;
    background: #ffa500;
    color: #000;
    border: none;
    padding: 15px;
    border-radius: 12px;
    font-weight: 800;
    font-size: 1rem;
    cursor: pointer;
    transition: 0.2s;
}

.btn-laby-orange:hover {
    background: #ffb533;
    transform: translateY(-2px);
}

.btn-laby-disabled {
    width: 100%;
    background: rgba(255, 255, 255, 0.05);
    color: #555;
    border: none;
    padding: 15px;
    border-radius: 12px;
    font-weight: 800;
    font-size: 1rem;
    cursor: default;
}

/* Milestones Container */
.streak-rewards-container {
    background: rgba(20, 20, 25, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.03);
    border-radius: 24px;
    padding: 0;
    overflow: hidden;
}

.streak-header-row {
    padding: 25px 30px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.03);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.info-hint {
    font-size: 0.75rem;
    color: #555;
    font-weight: 500;
}

.milestones-scroll-list {
    max-height: 600px;
    overflow-y: auto;
}

.milestones-scroll-list::-webkit-scrollbar { width: 6px; }
.milestones-scroll-list::-webkit-scrollbar-track { background: transparent; }
.milestones-scroll-list::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.05); border-radius: 10px; }

.milestone-row {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr;
    align-items: center;
    padding: 20px 30px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.02);
    transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    animation: slideInRow 0.5s ease backwards;
}

@keyframes slideInRow {
    from { transform: translateX(-20px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.row-progress-bg {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    width: var(--progress);
    background: linear-gradient(90deg, rgba(125, 64, 255, 0.05) 0%, rgba(255, 165, 0, 0.03) 100%);
    z-index: 1;
    transition: width 1s ease-out;
}

.milestone-row.status-reached .row-progress-bg {
    background: rgba(46, 204, 113, 0.03);
}

.milestone-row > div:not(.row-progress-bg) {
    position: relative;
    z-index: 2;
}

.milestone-row:last-child { border-bottom: none; }

.milestone-row:hover {
    background: rgba(255, 255, 255, 0.02);
    transform: scale(1.01);
}

.milestone-row.status-next {
    background: rgba(255, 165, 0, 0.01);
    border-left: 3px solid #ffa500;
}

.milestone-day-col {
    display: flex;
    align-items: center;
    gap: 12px;
}

.day-num {
    font-weight: 800;
    font-size: 1.1rem;
    color: #ffa500;
    min-width: 30px;
    text-shadow: 0 0 10px rgba(255, 165, 0, 0.2);
}

.dot { color: #444; }
.day-text { color: #888; font-weight: 500; }

.progress-tag {
    background: rgba(255, 165, 0, 0.1);
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.75rem;
    color: #ffa500;
    border: 1px solid rgba(255, 165, 0, 0.1);
}

.milestone-reward-col {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    color: #ccc;
}

.milestone-reward-col i { color: #ffa500; font-size: 0.9rem; }

.status-btn {
    display: inline-block;
    padding: 8px 15px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 700;
    text-align: center;
    min-width: 100px;
    transition: 0.3s;
}

.status-btn.reached {
    background: #ffa500;
    color: #000;
    box-shadow: 0 0 15px rgba(255, 165, 0, 0.2);
}

.status-btn.next {
    background: rgba(255, 165, 0, 0.1);
    color: #ffa500;
    border: 1px solid rgba(255, 165, 0, 0.2);
}

.status-btn.locked {
    background: rgba(255, 255, 255, 0.03);
    color: #444;
}

/* Success Animation */
@keyframes rewardCollect {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); filter: brightness(1.5); }
    100% { transform: scale(1); }
}

.collecting-anim {
    animation: rewardCollect 0.5s ease infinite;
}

/* Card Entrances */
.streak-banner-card {
    animation: fadeInDown 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.daily-claim-card {
    animation: fadeInDown 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) 0.1s backwards;
}

@keyframes fadeInDown {
    from { transform: translateY(-30px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

/* Status Reached Styles */
.status-reached .day-text { color: #eee; }
.status-reached .milestone-reward-col { color: #eee; }

@media (max-width: 800px) {
    .rewards-upper-grid { grid-template-columns: 1fr; }
    .milestone-row { grid-template-columns: 1fr 1fr; gap: 15px; }
    .milestone-status-col { grid-column: span 2; display: flex; justify-content: flex-end; }
}
</style>

<?php include 'includes/footer.php'; ?>
