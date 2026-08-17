<?php
// Maintenance Mode Page
ob_start();
session_start();
require_once 'includes/config.php';

// Check if maintenance is actually active
$maintenanceMode = false;
try {
    $stmtM = $pdo->prepare("SELECT stat_value FROM clan_stats WHERE stat_key = 'maintenance_mode'");
    $stmtM->execute();
    $maintenanceMode = (int)($stmtM->fetchColumn() ?: 0) === 1;
} catch (Exception $e) {}

// Auto-disable maintenance after release date: June 7, 2026 18:00
$releaseDate = strtotime("2026-06-07 18:00:00");
if (time() >= $releaseDate) {
    $maintenanceMode = false;
    // Optional: Update database to permanently disable maintenance mode
    try {
        $pdo->prepare("UPDATE clan_stats SET stat_value = '0' WHERE stat_key = 'maintenance_mode'")->execute();
    } catch (Exception $e) {}
}

// If maintenance is NOT active, redirect back home
if (!$maintenanceMode) {
    header('Location: index.php');
    exit;
}

$rootDir = (isset($base_path)) ? $base_path : '';

// Check if user is admin to allow bypass (only if they are already logged in)
$isAdmin = false;
$adminRoles = ['1499900998381080686', '1499900927723700375', '1499910122603020497', '1504599898698551336'];

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    $stmt = $pdo->prepare("SELECT roles, web_permission FROM users WHERE discord_id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $user = $stmt->fetch();
    if ($user) {
        $userRoles = json_decode($user['roles'] ?? '[]', true);
        foreach ($adminRoles as $role) {
            if (in_array($role, $userRoles)) {
                $isAdmin = true;
                break;
            }
        }
        if ($user['web_permission'] === 'Administrator') $isAdmin = true;
    }
}

// If admin and wants to bypass, they can go to login.php?bypass_maintenance=1 
// (or we can just let them through if they are already logged in)
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="30">
    <title>Wartungsarbeiten - Der Noxus [NOX]</title>
    <link rel="stylesheet" href="<?php echo $rootDir; ?>css/style.css">
    <link rel="icon" type="image/png" href="<?php echo $rootDir; ?>assets/img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #050510;
            color: #fff;
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .maintenance-container {
            max-width: 900px;
            width: 100%;
            padding: 40px;
            text-align: center;
            z-index: 10;
        }

        .maintenance-card {
            background: rgba(15, 15, 30, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(125, 64, 255, 0.2);
            border-radius: 40px;
            padding: 60px;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.6);
            position: relative;
            overflow: hidden;
            animation: fadeInScale 0.8s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        .maintenance-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 4px;
            background: linear-gradient(90deg, #7d40ff, #a855f7);
        }

        .v2-badge {
            display: inline-block;
            background: rgba(125, 64, 255, 0.15);
            color: #7d40ff;
            padding: 8px 20px;
            border-radius: 100px;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 20px;
            border: 1px solid rgba(125, 64, 255, 0.3);
            text-transform: uppercase;
            font-size: 0.8rem;
        }

        h1 {
            font-size: 4rem;
            font-weight: 900;
            margin: 0 0 10px;
            text-transform: uppercase;
            letter-spacing: -2px;
            background: linear-gradient(to bottom, #fff, #aaa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .subtitle {
            font-size: 1.5rem;
            color: #7d40ff;
            font-weight: 700;
            margin-bottom: 40px;
            text-transform: uppercase;
        }

        /* Countdown Style */
        .countdown-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin: 40px 0;
        }

        .countdown-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 20px;
            position: relative;
            transition: transform 0.3s ease;
        }

        .countdown-item:hover {
            transform: translateY(-5px);
            border-color: rgba(125, 64, 255, 0.3);
        }

        .countdown-value {
            display: block;
            font-size: 2.5rem;
            font-weight: 900;
            color: #fff;
            line-height: 1;
        }

        .countdown-label {
            display: block;
            font-size: 0.7rem;
            color: #7d40ff;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 1px;
            margin-top: 5px;
        }

        /* Info Content */
        .v2-info {
            margin-top: 50px;
            text-align: left;
            background: rgba(0, 0, 0, 0.2);
            padding: 30px;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.03);
        }

        .v2-info h3 {
            margin: 0 0 15px;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .v2-info ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .v2-info li {
            position: relative;
            padding-left: 20px;
            color: #ccc;
            font-size: 0.9rem;
        }

        .v2-info li::before {
            content: '›';
            position: absolute;
            left: 0;
            color: #7d40ff;
            font-weight: bold;
        }

        .admin-login {
            margin-top: 40px;
        }

        .btn-admin {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            padding: 12px 30px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-admin:hover {
            background: rgba(125, 64, 255, 0.1);
            border-color: rgba(125, 64, 255, 0.3);
            color: #7d40ff;
        }

        @keyframes fadeInScale {
            0% { opacity: 0; transform: scale(0.95); }
            100% { opacity: 1; transform: scale(1); }
        }

        @media (max-width: 768px) {
            h1 { font-size: 2.5rem; }
            .countdown-grid { grid-template-columns: 1fr 1fr; }
            .v2-info ul { grid-template-columns: 1fr; }
            .maintenance-card { padding: 30px; }
        }

        /* Animated Blobs */
        .blobs {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            overflow: hidden;
            z-index: -1;
        }

        .blob {
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(125, 64, 255, 0.1) 0%, rgba(125, 64, 255, 0) 70%);
            border-radius: 50%;
            filter: blur(80px);
            animation: move 20s infinite alternate;
        }

        .blob-1 { top: -250px; left: -250px; }
        .blob-2 { bottom: -250px; right: -250px; animation-delay: -5s; }

        @keyframes move {
            0% { transform: translate(0, 0); }
            100% { transform: translate(100px, 100px); }
        }
    </style>
</head>
<body>
    <div class="blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
    </div>

    <div class="maintenance-container">
        <div class="maintenance-card">
            <div class="v2-badge">Development v2.0.0</div>
            <h1>In Wartung</h1>
            <div class="subtitle">Wir bauen an etwas Großem.</div>
            
            <p style="color: #888; margin-bottom: 20px;">Wir bereiten den Noxus auf die nächste Ära vor.</p>

            <div class="countdown-grid" id="countdown">
                <div class="countdown-item">
                    <span class="countdown-value" id="days">00</span>
                    <span class="countdown-label">Tage</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-value" id="hours">00</span>
                    <span class="countdown-label">Stunden</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-value" id="minutes">00</span>
                    <span class="countdown-label">Minuten</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-value" id="seconds">00</span>
                    <span class="countdown-label">Sekunden</span>
                </div>
            </div>

            <div class="v2-info">
                <h3><i class="fas fa-rocket" style="color: #7d40ff;"></i> Was dich in v2.0.0 erwartet:</h3>
                <ul>
                    <li>Neuer Noxus-Bot v2.0.0</li>
                    <li>Komplettes Website Redesign</li>
                    <li>Echtzeit Clan-Statistiken</li>
                    <li>Erweitertes Level-System</li>
                    <li>Self-Service Rollen-System</li>
                    <li>Integrierter Marktplatz</li>
                    <li>Clan-Achievements</li>
                    <li>Automatisierte Events</li>
                </ul>
                <p style="margin-top: 20px; font-size: 0.85rem; color: #666; text-align: center;">
                    Geplanter Release: <strong style="color: #7d40ff;">07.06.2026 - 18:00 Uhr</strong>
                </p>
            </div>

            <div class="admin-login">
                <?php if ($isAdmin): ?>
                    <a href="admin.php" class="btn-admin">
                        <i class="fas fa-user-shield"></i> Zurück zum Admin-Panel
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn-admin">
                        <i class="fab fa-discord"></i> Admin Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const targetDate = new Date("June 7, 2026 18:00:00").getTime();

        function updateCountdown() {
            const now = new Date().getTime();
            const distance = targetDate - now;

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById("days").innerText = days.toString().padStart(2, '0');
            document.getElementById("hours").innerText = hours.toString().padStart(2, '0');
            document.getElementById("minutes").innerText = minutes.toString().padStart(2, '0');
            document.getElementById("seconds").innerText = seconds.toString().padStart(2, '0');

            if (distance < 0) {
                clearInterval(timer);
                document.getElementById("countdown").innerHTML = "V2.0.0 IST DA!";
                // Reload page when countdown reaches 0 to trigger the PHP redirect
                setTimeout(() => { location.reload(); }, 2000);
            }
        }

        const timer = setInterval(updateCountdown, 1000);
        updateCountdown();
    </script>
</body>
</html>
