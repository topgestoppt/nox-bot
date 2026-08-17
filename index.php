<?php 
include 'includes/header.php'; 

// Statistiken abrufen
$clanMemberCount = 0;
$communityMemberCount = 0;

try {
    $stmt = $pdo->query("SELECT stat_key, stat_value FROM clan_stats");
    $stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $clanMemberCount = $stats['member_count'] ?? 0;
    $communityMemberCount = $stats['discord_member_count'] ?? 0;
} catch (Exception $e) {
    // Fallback
}
?>

<section class="hero">
    <div class="hero-bg-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    <div class="hero-content">
        <div class="hero-logo-wrapper">
            <img src="assets/img/logo_mitte.png" alt="Noxus Logo" class="hero-logo">
        </div>
        <span class="hero-welcome">Willkommen bei</span>
        <h1 class="glow-text">Der Noxus [NOX]</h1>
        <p class="hero-description">
            Zusammen spielen, zusammen wachsen. Ein Minecraft-Clan auf <strong>OPSUCHT</strong> mit Leuten die zusammenhalten — und einem System das einfach funktioniert.
        </p>
        
        <div class="hero-stats">
            <div class="stat-item" style="--delay: 0.1s">
                <span class="stat-value"><?php echo $clanMemberCount; ?></span>
                <span class="stat-label">Clan-Mitglieder</span>
            </div>
            <div class="stat-item" style="--delay: 0.2s">
                <span class="stat-value"><?php echo $communityMemberCount; ?></span>
                <span class="stat-label">Community-Mitglieder</span>
            </div>
            <div class="stat-item" style="--delay: 0.3s">
                <span class="stat-value">24/7</span>
                <span class="stat-label">Aktiv</span>
            </div>
            <div class="stat-item" style="--delay: 0.4s">
                <span class="stat-value">2026</span>
                <span class="stat-label">Gegründet</span>
            </div>
        </div>
    </div>
</section>

<section class="about-noxus" data-reveal>
    <div class="container">
        <h2 class="section-title">Was macht uns aus?</h2>
        <h3 class="section-subtitle">Wir sind nicht nur ein Minecraft Clan!</h3>
        <p class="section-description">
            Noxus ist eine Gemeinschaft, die über das Spiel hinausgeht. Wir bieten eine strukturierte Umgebung, in der jeder Spieler seine Stärken einbringen kann – ob als Händler, Architekt oder Organisator.
        </p>
        <div class="about-buttons">
            <a href="about.php" class="btn-minecraft">
                <span class="btn-text">Mehr Informationen gesucht?</span>
                <span class="btn-subtext">Über Uns</span>
            </a>
            <a href="rules.php" class="btn-minecraft secondary">
                <span class="btn-text">Regelwerk anschauen</span>
                <span class="btn-subtext">Unsere Richtlinien</span>
            </a>
        </div>
    </div>
</section>

<section class="features" data-reveal>
    <div class="container">
        <h2 class="section-title">Was wir noch so alles bieten</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-gavel"></i></div>
                <h3>Auktionen</h3>
                <p>Regelmäßige Clan-Auktionen für seltene Items und exklusive Vorteile.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-store"></i></div>
                <h3>Eigener Markt</h3>
                <p>Ein faires Handelssystem innerhalb des Clans für maximale Gewinne.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-graduation-cap"></i></div>
                <h3>Kurse & Training</h3>
                <p>Lerne von den Besten in unseren internen Bau- und Farm-Workshops.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-calendar-check"></i></div>
                <h3>Clan-Events</h3>
                <p>Wöchentliche Events mit großartigen Belohnungen und viel Spaß.</p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
