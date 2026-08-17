<?php
include 'includes/header.php';

// Role configuration and sorting (same as admin.php for consistency)
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
    '1502647723843653662' => '🌑〡Schattenkind'
];

// Fetch all clan members
try {
    $stmt = $pdo->query("SELECT * FROM users WHERE is_clan_member = 1 AND is_bot = 0 ORDER BY username ASC");
    $allMembers = $stmt->fetchAll();
} catch (Exception $e) {
    $allMembers = [];
}

$groupedMembers = [];
foreach ($rolePriority as $id => $name) {
    $groupedMembers[$id] = [
        'name' => $name,
        'users' => []
    ];
}

foreach ($allMembers as $user) {
    $userRoles = json_decode($user['roles'] ?? '[]', true);
    $found = false;
    
    foreach ($rolePriority as $roleId => $roleName) {
        if (in_array($roleId, $userRoles)) {
            $groupedMembers[$roleId]['users'][] = $user;
            $found = true;
            break;
        }
    }
}

// Remove empty groups
$groupedMembers = array_filter($groupedMembers, function($group) {
    return !empty($group['users']);
});

?>

<link rel="stylesheet" href="css/minecraft-style.css">

<style>
    .members-hero {
        padding: 180px 0 100px;
        background: linear-gradient(rgba(5, 5, 16, 0.8), rgba(5, 5, 16, 0.9)), 
                    url('https://wallpapers.com/images/hd/minecraft-pictures-wc3wk7lnm3zulf7g.jpg') center/cover no-repeat;
        position: relative;
        overflow: hidden;
        text-align: center;
        background-attachment: fixed;
    }

    .members-hero-content {
        position: relative;
        z-index: 2;
        max-width: 900px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .pixel-title-members {
        font-family: 'VT323', monospace;
        font-size: 5rem;
        margin-bottom: 20px;
        background: linear-gradient(to bottom, #fff 0%, var(--primary-purple) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        filter: drop-shadow(0 0 15px rgba(125, 64, 255, 0.4));
        animation: fadeInUp 1s ease-out;
        text-transform: uppercase;
    }

    .members-subtitle {
        font-size: 1.4rem;
        color: var(--text-muted);
        margin-bottom: 40px;
        animation: fadeInUp 1s ease-out 0.2s both;
    }

    .rank-section {
        margin-bottom: 100px;
        position: relative;
    }

    .rank-divider {
        display: flex;
        align-items: center;
        gap: 25px;
        margin-bottom: 50px;
    }

    .rank-divider h2 {
        font-family: 'VT323', monospace;
        font-size: 2.8rem;
        margin: 0;
        color: var(--primary-purple);
        white-space: nowrap;
        text-shadow: 0 0 15px rgba(125, 64, 255, 0.3);
    }

    .rank-line {
        height: 2px;
        flex-grow: 1;
        background: linear-gradient(to right, var(--primary-purple), transparent);
        opacity: 0.3;
    }

    .members-modern-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 30px;
    }

    .member-glass-card {
        background: rgba(125, 64, 255, 0.03);
        border: 1px solid var(--glass-border);
        border-radius: 24px;
        padding: 40px 30px;
        text-align: center;
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .member-glass-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle at top right, rgba(125, 64, 255, 0.1), transparent 70%);
        opacity: 0;
        transition: opacity 0.4s ease;
    }

    .member-glass-card:hover {
        transform: translateY(-15px);
        background: rgba(125, 64, 255, 0.08);
        border-color: var(--primary-purple);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 0 0 20px rgba(125, 64, 255, 0.2);
    }

    .member-glass-card:hover::before {
        opacity: 1;
    }

    .avatar-container {
        position: relative;
        width: 130px;
        height: 130px;
        margin-bottom: 25px;
        z-index: 2;
    }

    .avatar-frame {
        width: 100%;
        height: 100%;
        border-radius: 20px;
        object-fit: cover;
        border: 2px solid var(--glass-border);
        transition: all 0.4s ease;
        image-rendering: pixelated;
        background: #1a1a2e;
    }

    .member-glass-card:hover .avatar-frame {
        transform: scale(1.05) rotate(3deg);
        border-color: var(--primary-purple);
        box-shadow: 0 0 20px rgba(125, 64, 255, 0.3);
    }

    .member-details {
        z-index: 2;
    }

    .member-user-name {
        font-family: 'VT323', monospace;
        font-size: 2.2rem;
        color: #fff;
        margin-bottom: 8px;
        display: block;
        transition: color 0.3s ease;
    }

    .member-glass-card:hover .member-user-name {
        color: var(--primary-purple);
    }

    .ingame-tag {
        font-size: 0.9rem;
        color: var(--text-muted);
        background: rgba(255, 255, 255, 0.05);
        padding: 4px 12px;
        border-radius: 50px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid var(--glass-border);
    }

    .ingame-tag i {
        font-size: 0.8rem;
        color: var(--primary-purple);
    }

    .minecraft-head {
        width: 14px;
        height: 14px;
        border-radius: 2px;
        image-rendering: pixelated;
    }

    .no-members-box {
        padding: 80px;
        text-align: center;
        background: rgba(125, 64, 255, 0.03);
        border: 1px solid var(--glass-border);
        border-radius: 30px;
        margin: 50px 0;
    }

    .no-members-box i {
        font-size: 4rem;
        color: var(--text-muted);
        margin-bottom: 20px;
    }

    @media (max-width: 768px) {
        .pixel-title-members {
            font-size: 3.5rem;
        }
        .rank-divider h2 {
            font-size: 2rem;
        }
    }
</style>

<section class="members-hero">
    <div class="hero-bg-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    <div class="members-hero-content">
        <span class="hero-welcome">Das Kollektiv</span>
        <h1 class="pixel-title-members">Clan-Mitglieder</h1>
        <p class="members-subtitle">
            Die Legenden und Schattenkinder, die den Noxus-Clan zu dem machen, was er heute ist.
        </p>
        <div class="hero-stats" style="margin-top: 20px;">
            <div class="stat-item" style="--delay: 0.1s">
                <span class="stat-value"><?php echo count($allMembers); ?></span>
                <span class="stat-label">Gesamt</span>
            </div>
            <div class="stat-item" style="--delay: 0.2s">
                <span class="stat-value"><?php echo count($groupedMembers); ?></span>
                <span class="stat-label">Ränge</span>
            </div>
            <div class="stat-item" style="--delay: 0.3s">
                <span class="stat-value">Aktiv</span>
                <span class="stat-label">Status</span>
            </div>
        </div>
    </div>
</section>

<div class="section-divider"></div>

<div class="container" style="padding-top: 50px; padding-bottom: 100px;">
    <?php if (empty($groupedMembers)): ?>
        <div class="no-members-box" data-reveal>
            <i class="fas fa-users-slash"></i>
            <h2>Keine Mitglieder gefunden</h2>
            <p>Es konnten aktuell keine Mitglieder in der Datenbank gefunden werden.</p>
        </div>
    <?php else: ?>
        <?php foreach ($groupedMembers as $roleId => $group): ?>
            <section class="rank-section" data-reveal>
                <div class="rank-divider">
                    <div class="rank-line"></div>
                    <h2><i class="fas fa-shield-alt" style="margin-right: 15px; font-size: 0.8em;"></i> <?php echo htmlspecialchars($group['name']); ?></h2>
                    <div class="rank-line" style="background: linear-gradient(to left, var(--primary-purple), transparent);"></div>
                </div>
                
                <div class="members-modern-grid">
                    <?php foreach ($group['users'] as $member): 
                        $meta = json_decode($member['clan_meta'] ?? '{}', true);
                        $ingameName = $meta['ingame_name'] ?? 'Unbekannt';
                        
                        // Namen für Anzeige (Punkte bleiben)
                        $displayUsername = $member['username'];
                        $displayIngameName = $ingameName;
                        
                        // Name für Minecraft-Head (Punkte entfernen)
                        $headName = str_replace('.', '', $ingameName);
                        
                        // Haupt-Avatar ist IMMER Discord
                        $avatarUrl = $member['avatar'] 
                            ? "https://cdn.discordapp.com/avatars/{$member['discord_id']}/{$member['avatar']}.png" 
                            : "https://cdn.discordapp.com/embed/avatars/0.png";
                    ?>
                        <div class="member-glass-card">
                            <div class="avatar-container">
                                <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="<?php echo htmlspecialchars($displayUsername); ?>" class="avatar-frame">
                            </div>
                            <div class="member-details">
                                <span class="member-user-name"><?php echo htmlspecialchars($displayUsername); ?></span>
                                <div class="ingame-tag">
                                    <img src="https://mc-heads.net/avatar/<?php echo ($headName !== 'Unbekannt' ? htmlspecialchars($headName) : 'Steve'); ?>/16" alt="MC-Head" class="minecraft-head">
                                    <span><?php echo htmlspecialchars($displayIngameName); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 40px;">
        <a href="index.php" class="btn-secondary" style="border-radius: 50px; padding: 15px 40px;">
            <i class="fas fa-arrow-left" style="margin-right: 10px;"></i> Zurück zum Spawn
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
