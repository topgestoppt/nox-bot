<?php
require_once 'includes/config.php';
session_start();

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header('Location: login.php');
    exit;
}

// OAuth2 Logic
if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Exchange code for token
    $token_url = 'https://discord.com/api/oauth2/token';
    $data = array(
        'client_id' => DISCORD_CLIENT_ID,
        'client_secret' => DISCORD_CLIENT_SECRET,
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => DISCORD_REDIRECT_URI,
        'scope' => 'identify email guilds.join'
    );

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($token_url, false, $context);
    $token_data = json_decode($result, true);

    if (isset($token_data['access_token'])) {
        $access_token = $token_data['access_token'];

        // Get user info
        $user_url = 'https://discord.com/api/users/@me';
        $user_options = array(
            'http' => array(
                'header' => "Authorization: Bearer $access_token\r\n",
                'method' => 'GET'
            )
        );
        $user_context = stream_context_create($user_options);
        $user_result = file_get_contents($user_url, false, $user_context);
        $user_data = json_decode($user_result, true);

        if (isset($user_data['id'])) {
            $discordId = $user_data['id'];
            $username = $user_data['username'];

            // Fetch Roles immediately from Discord API via Bot Token
            $roles = [];
            $guild_member_url = "https://discord.com/api/guilds/" . DISCORD_GUILD_ID . "/members/" . $discordId;
            $guild_member_options = array(
                'http' => array(
                    'header' => "Authorization: Bot " . DISCORD_BOT_TOKEN . "\r\n",
                    'method' => 'GET',
                    'ignore_errors' => true
                )
            );
            $guild_member_context = stream_context_create($guild_member_options);
            $guild_member_result = file_get_contents($guild_member_url, false, $guild_member_context);
            if ($guild_member_result) {
                $guild_member_data = json_decode($guild_member_result, true);
                if (isset($guild_member_data['roles'])) {
                    $roles = $guild_member_data['roles'];
                }
            }
            $roles_json = json_encode($roles);

            // In login.php das SQL-Statement anpassen:
            try {
                $discordJoinedAt = null;
                if (isset($guild_member_data['joined_at'])) {
                    $discordJoinedAt = date('Y-m-d H:i:s', strtotime($guild_member_data['joined_at']));
                }

                $stmt = $pdo->prepare("INSERT INTO users (discord_id, username, avatar, email, access_token, processed, roles, joined_at) 
                           VALUES (?, ?, ?, ?, ?, 1, ?, ?) 
                           ON DUPLICATE KEY UPDATE 
                           username = ?, avatar = ?, email = ?, access_token = ?, processed = 1, roles = ?,
                           joined_at = IFNULL(joined_at, ?)");

                $stmt->execute([
                    $user_data['id'], $user_data['username'], $user_data['avatar'], $user_data['email'], $access_token, $roles_json, $discordJoinedAt,
                    $user_data['username'], $user_data['avatar'], $user_data['email'], $access_token, $roles_json, $discordJoinedAt
                ]);
            } catch (Exception $e) {
                // Falls 'processed' oder 'roles' fehlt, Spalten anlegen (Notfall-Fix)
                try { $pdo->exec("ALTER TABLE users ADD processed TINYINT(1) DEFAULT 0"); } catch (Exception $ex) {}
                try { $pdo->exec("ALTER TABLE users ADD roles TEXT DEFAULT NULL"); } catch (Exception $ex) {}
                try { $pdo->exec("ALTER TABLE users ADD joined_at DATETIME DEFAULT NULL"); } catch (Exception $ex) {}
                
                $discordJoinedAt = null;
                if (isset($guild_member_data['joined_at'])) {
                    $discordJoinedAt = date('Y-m-d H:i:s', strtotime($guild_member_data['joined_at']));
                }

                $stmt = $pdo->prepare("INSERT INTO users (discord_id, username, avatar, email, access_token, processed, roles, joined_at) 
                           VALUES (?, ?, ?, ?, ?, 1, ?, ?) 
                           ON DUPLICATE KEY UPDATE 
                           username = ?, avatar = ?, email = ?, access_token = ?, processed = 1, roles = ?,
                           joined_at = IFNULL(joined_at, ?)");
                $stmt->execute([
                    $user_data['id'], $user_data['username'], $user_data['avatar'], $user_data['email'], $access_token, $roles_json, $discordJoinedAt,
                    $user_data['username'], $user_data['avatar'], $user_data['email'], $access_token, $roles_json, $discordJoinedAt
                ]);
            }

            // In Warteschlange für den Discord-Bot einreihen (Cross-Server kompatibel)
            $stmtQueue = $pdo->prepare("INSERT INTO pending_notifications (discord_id, username, module_key) VALUES (?, ?, 'registration')");
            $stmtQueue->execute([$discordId, $username]);

            $_SESSION['user'] = $user_data;
            $_SESSION['logged_in'] = true;

            header('Location: index.php');
            exit;
        }
    }
}

// Generate Discord Login URL
$params = array(
    'client_id' => DISCORD_CLIENT_ID,
    'redirect_uri' => DISCORD_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'identify email guilds.join'
);
$login_url = 'https://discord.com/api/oauth2/authorize?' . http_build_query($params);

include 'includes/header.php'; 
?>

<section class="login-section">
    <div class="login-card">
        <div class="login-logo">
            <img src="assets/img/logo_mitte.png" alt="Logo">
        </div>
        <h2>Discord Login</h2>
        <p class="login-desc">Melde dich mit deinem Discord-Account an, um alle Funktionen nutzen zu können.</p>
        
        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
            <div class="logged-in-info">
                <p>Eingeloggt als <strong><?php echo htmlspecialchars($_SESSION['user']['username']); ?></strong></p>
                <a href="login.php?action=logout" class="btn-discord">Logout</a>
            </div>
        <?php else: ?>
            <a href="<?php echo $login_url; ?>" class="btn-discord">
                <i class="fab fa-discord"></i> Mit Discord anmelden
            </a>
        <?php endif; ?>
        
        <p class="login-footer">
            Mit der Anmeldung stimmst du unseren <a href="#">Nutzungsbedingungen</a> und der <a href="#">Datenschutzerklärung</a> zu.
        </p>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
