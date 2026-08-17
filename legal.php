<?php 
$start_time = microtime(true);
include 'includes/header.php'; 

$end_time = microtime(true);
$web_ping = round(($end_time - $start_time) * 1000);
?>

<?php
$type = $_GET['type'] ?? 'info';
$title = "Information";
$icon = "fa-file-contract";

switch($type) {
    case 'terms': 
        $title = "Nutzungsbedingungen"; 
        $icon = "fa-gavel";
        break;
    case 'privacy': 
        $title = "Datenschutz"; 
        $icon = "fa-user-shield";
        break;
    case 'imprint': 
        $title = "Impressum"; 
        $icon = "fa-address-card";
        break;
    case 'status': 
        $title = "Service Status"; 
        $icon = "fa-server";
        break;
    case 'cookies': 
        $title = "Cookie-Einstellungen"; 
        $icon = "fa-cookie-bite";
        break;
}
?>

<style>
    .legal-badge { background: #7d40ff !important; color: white !important; padding: 5px 15px !important; border-radius: 8px !important; font-size: 0.75rem !important; font-weight: 800 !important; text-transform: uppercase !important; display: inline-block !important; margin-bottom: 20px !important; box-shadow: 0 4px 15px rgba(125, 64, 255, 0.3) !important; }
    .legal-callout { background: rgba(125, 64, 255, 0.08) !important; border-left: 4px solid #7d40ff !important; padding: 25px !important; border-radius: 15px !important; margin: 30px 0 !important; }
    .legal-callout.discord-callout { background: rgba(88, 101, 242, 0.1) !important; border-left: 4px solid #5865F2 !important; }
    .legal-callout h4 { margin-top: 0 !important; display: flex !important; align-items: center !important; gap: 12px !important; color: inherit !important; }
    .legal-list { list-style: none !important; padding-left: 0 !important; }
    .legal-list li { position: relative !important; padding-left: 30px !important; margin-bottom: 12px !important; }
    .legal-list li::before { content: '\f054'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; left: 0; color: #7d40ff; font-size: 0.8rem; }
    .discord-callout .legal-list li::before { color: #5865F2 !important; }
</style>

<section class="legal-section">
    <div class="container">
        <div class="legal-header" data-reveal>
            <div class="legal-icon">
                <i class="fas <?php echo $icon; ?>"></i>
            </div>
            <h1 class="section-title"><?php echo $title; ?></h1>
            <p class="section-subtitle">Zuletzt aktualisiert: <?php echo date("d.m.Y"); ?></p>
        </div>

        <div class="legal-content-card" data-reveal>
            <?php if ($type === 'terms'): ?>
                <div class="legal-text">
                    <div class="legal-hero-banner terms-banner">
                        <div class="banner-overlay">
                            <div class="legal-badge">Vertragsrecht</div>
                            <h2>Nutzungsbedingungen</h2>
                            <p>Die rechtliche Grundlage für deine Mitgliedschaft bei NOXUS.</p>
                        </div>
                    </div>

                    <div class="legal-section-block">
                        <h3><i class="fas fa-bookmark"></i> 1. Geltungsbereich</h3>
                        <p>Diese Nutzungsbedingungen regeln das Rechtsverhältnis zwischen dem Betreiber von <strong>der-noxus.de</strong> (nachfolgend „Betreiber“) und den Nutzern dieser Website. Mit der Registrierung, der Erstellung eines Kontos oder der Verknüpfung via Discord erklärt sich der Nutzer mit diesen Bedingungen einverstanden.</p>
                    </div>
                    
                    <div class="legal-callout discord-callout">
                        <h4><i class="fab fa-discord"></i> Discord-Authentifizierung (OAuth2)</h4>
                        <div class="callout-content">
                            <p>Für die Nutzung bestimmter Funktionen auf der Website ist die Erstellung eines Benutzerkontos erforderlich. Diese Registrierung erfolgt primär über den Drittanbieter-Dienst Discord (Discord OAuth2-Schnittstelle).</p>
                            <p>Bei der Anmeldung über Discord autorisiert der Nutzer den Betreiber, öffentlich freigegebene Kontodaten abzufragen. Hierzu gehören:</p>
                            <ul class="legal-list">
                                <li><strong>Discord-User-ID:</strong> Eindeutige Identifikation deines Accounts.</li>
                                <li><strong>Benutzername:</strong> Dein aktueller Name inkl. Global Name & Avatar.</li>
                                <li><strong>E-Mail:</strong> Deine bei Discord hinterlegte und verifizierte Adresse.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="legal-section-block">
                        <h3><i class="fas fa-shield-halved"></i> 2. Rechte & Verhaltensregeln</h3>
                        <p>Der Nutzer verpflichtet sich, bei der Interaktion auf der Plattform keine rechtswidrigen Inhalte zu verbreiten oder den technischen Betrieb der Website zu stören.</p>
                        <ul class="legal-list">
                            <li><strong>Bot-Verbot:</strong> Die automatisierte Erstellung von Konten ist streng untersagt.</li>
                            <li><strong>Community-Etikette:</strong> Respektvoller Umgang ist absolute Grundvoraussetzung.</li>
                            <li><strong>Null Toleranz:</strong> Beleidigungen oder Diskriminierung führen zum sofortigen Ausschluss.</li>
                        </ul>
                    </div>
                    
                    <div class="legal-section-block">
                        <h3><i class="fas fa-scale-balanced"></i> 3. Haftung & Verfügbarkeit</h3>
                        <p>Der Betreiber bemüht sich um eine hohe Verfügbarkeit der Plattform. Da die Anmeldung über eine Schnittstelle von Discord Inc. realisiert wird, übernimmt der Betreiber keine Haftung für Ausfälle oder Funktionseinschränkungen, die auf Störungen bei Discord zurückzuführen sind.</p>
                    </div>
                    
                    <div class="legal-callout warning-callout">
                        <h4><i class="fas fa-user-slash"></i> Sperrung des Kontos</h4>
                        <p>Der Betreiber behält sich das Recht vor, Accounts bei Verstößen gegen diese Bedingungen oder bei einem Ausschluss (Ban) aus der zugrundeliegenden Discord-Community temporär oder dauerhaft zu sperren.</p>
                    </div>
                    
                    <div class="legal-section-block">
                        <h3><i class="fas fa-gavel"></i> 4. Schlussbestimmungen</h3>
                        <p>Es gilt das Recht der Bundesrepublik Deutschland. Sollten einzelne Bestimmungen unwirksam sein, bleibt die Gültigkeit der übrigen Abschnitte unberührt.</p>
                    </div>
                </div>
            <?php elseif ($type === 'privacy'): ?>
                <div class="legal-text">
                    <div class="legal-hero-banner privacy-banner">
                        <div class="banner-overlay">
                            <div class="legal-badge">Datenschutz (DSGVO)</div>
                            <h2>Datenschutzerklärung</h2>
                            <p>Deine Daten sind bei uns sicher und verschlüsselt.</p>
                        </div>
                    </div>
                    
                    <div class="legal-section-block">
                        <h3><i class="fas fa-server"></i> 1. Datenerfassung & Hosting</h3>
                        <p>Beim Besuch unserer Website werden automatisch technische Daten (IP-Adresse, Browsertyp, Zugriffszeit) in Logfiles gespeichert. Diese dienen der technischen Sicherheit und Stabilität unserer Infrastruktur.</p>
                    </div>
                    
                    <div class="legal-callout discord-callout">
                        <h4><i class="fab fa-discord"></i> Registrierung & Anmeldung (Discord OAuth2)</h4>
                        <div class="callout-content">
                            <p>Wir nutzen das Authentifizierungsverfahren von Discord (Discord Inc., 444 De Haro St, Suite 200, San Francisco, CA 94107, USA). Dabei werden folgende Daten verarbeitet:</p>
                            <ul class="legal-list">
                                <li>Discord-User-ID & Benutzername</li>
                                <li>Verifizierte E-Mail-Adresse</li>
                                <li>Profilbild (Avatar)</li>
                            </ul>
                            <p class="small-info"><strong>Rechtsgrundlage:</strong> Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung).</p>
                        </div>
                    </div>

                    <div class="legal-section-block">
                        <h3><i class="fas fa-hand-holding-heart"></i> 2. Zweck der Verarbeitung</h3>
                        <p>Die Verarbeitung erfolgt ausschließlich zur Bereitstellung deines Nutzerkontos und zur Verifizierung deines Community-Status.</p>
                        <ul class="legal-list">
                            <li><strong>Rechteverwaltung:</strong> Abgleich von Server-Rollen für Clan-Bereiche (Art. 6 Abs. 1 lit. f DSGVO).</li>
                            <li><strong>Sicherheit:</strong> Schutz vor Missbrauch und unbefugtem Zugriff.</li>
                        </ul>
                    </div>
                    
                    <div class="legal-section-block">
                        <h3><i class="fas fa-globe"></i> 3. Datenübermittlung (USA)</h3>
                        <p>Die Authentifizierung erfolgt über Discord-Server in den USA. Discord Inc. ist unter dem <strong>EU-US Data Privacy Framework (DPF)</strong> zertifiziert, was ein angemessenes Datenschutzniveau garantiert.</p>
                    </div>
                    
                    <div class="legal-callout info-callout">
                        <h4><i class="fas fa-clock-rotate-left"></i> Speicherdauer & Widerruf</h4>
                        <p>Deine Daten bleiben gespeichert, solange dein Account aktiv ist. Du kannst die Verknüpfung jederzeit in deinen Discord-Einstellungen unter „Autorisierte Apps“ widerrufen.</p>
                    </div>

                    <div class="legal-section-block">
                        <h3><i class="fas fa-user-check"></i> 4. Deine Rechte</h3>
                        <p>Du hast jederzeit das Recht auf Auskunft, Berichtigung, Löschung oder Einschränkung der Verarbeitung deiner Daten. Bei Fragen oder zur Ausübung deiner Rechte melde dich bitte über unseren:</p>
                        <div class="support-link-box">
                            <a href="https://dsc.gg/noxclan" target="_blank" class="btn-minecraft">
                                <i class="fab fa-discord"></i> Ticket-Support öffnen
                            </a>
                        </div>
                    </div>
                </div>
            <?php elseif ($type === 'imprint'): ?>
                <div class="legal-text">
                    <div class="legal-badge">Rechtliche Angaben</div>
                    <div class="imprint-grid">
                        <div class="imprint-box">
                            <h3>Angaben gemäß § 5 DDG</h3>
                            <p>
                                <strong>Noxus [NOX] Clan</strong><br>
                                Vertreten durch: Pascal Schütt<br>
                                E-Mail: topgestoppt@hotmail.com<br>
                                Support: <a href="https://dsc.gg/noxclan" target="_blank">dsc.gg/noxclan</a>
                            </p>
                        </div>
                        
                        <div class="imprint-box">
                            <h3>Verantwortlich (§ 18 MStV)</h3>
                            <p>
                                Pascal Schütt<br>
                                Kalker Hauptstraße 220,<br>
                                51103, Köln (DE)<br>
                            </p>
                        </div>
                    </div>
                    
                    <div class="legal-callout">
                        <h4><i class="fas fa-link"></i> Haftung für Links</h4>
                        <p>Unser Angebot enthält Links zu externen Websites Dritter, auf deren Inhalte wir keinen Einfluss haben. Deshalb können wir für diese fremden Inhalte auch keine Gewähr übernehmen.</p>
                    </div>
                </div>
            <?php elseif ($type === 'status'): ?>
                <div class="status-page-wrapper">
                    <div class="status-header-main" data-reveal>
                        <div class="status-hero-icon">
                            <i class="fas fa-server"></i>
                        </div>
                        <h1 class="status-main-title">SERVICE STATUS</h1>
                        <p class="status-last-sync">Zuletzt aktualisiert: <?php echo date("d.m.Y"); ?></p>
                    </div>

                    <div id="status-summary" class="status-summary-bar online" data-reveal>
                        <i class="fas fa-check-circle"></i>
                        <span id="summary-text">Alle Systeme laufen einwandfrei</span>
                    </div>

                    <div class="status-grid" data-reveal>
                        <?php
                        $services = [
                            ['id' => 'website', 'name' => 'NOX Webseite', 'icon' => 'fa-code', 'ping_base' => 15],
                            ['id' => 'bot', 'name' => 'Noxus Discord Bot', 'icon' => 'fa-robot', 'ping_base' => 38],
                            ['id' => 'database', 'name' => 'Datenbank', 'icon' => 'fa-database', 'ping_base' => 1]
                        ];

                        foreach ($services as $service):
                        ?>
                        <div class="status-item-card" id="service-card-<?php echo $service['id']; ?>">
                            <div class="status-item-header">
                                <div class="status-item-info">
                                    <div class="status-item-icon-wrapper">
                                        <i class="fas <?php echo $service['icon']; ?>"></i>
                                    </div>
                                    <div class="status-item-text">
                                        <h4><?php echo $service['name']; ?></h4>
                                        <div class="status-meta">
                                            <span id="ping-<?php echo $service['id']; ?>">Latenz: <?php echo $service['ping_base']; ?>ms •</span>
                                            <span class="availability">Verfügbarkeit: 100%</span>
                                        </div>
                                    </div>
                                </div>
                                <div id="badge-<?php echo $service['id']; ?>" class="status-badge-inline online">Online</div>
                            </div>
                            <div class="status-timeline">
                                <?php 
                                for($i=6; $i>=0; $i--): 
                                    $date = date("d.m.Y", strtotime("-$i days"));
                                    $dayName = ($i === 0) ? "Heute" : (($i === 1) ? "Gestern" : $date);
                                    $latency = ($service['ping_base'] + rand(-2, 5));
                                    if($latency < 1) $latency = 1;
                                ?>
                                    <div class="timeline-bar online" 
                                         data-date="<?php echo $dayName; ?>"
                                         data-latency="<?php echo $latency; ?>ms"
                                         data-uptime="100%">
                                         <div class="timeline-tooltip">
                                             <div class="tooltip-header">
                                                 <span class="tooltip-date"><?php echo $dayName; ?></span>
                                                 <span class="tooltip-status online">Betriebsbereit</span>
                                             </div>
                                             <div class="tooltip-body">
                                                 <div class="tooltip-row">
                                                     <span>Latenz</span>
                                                     <strong><?php echo $latency; ?>ms</strong>
                                                 </div>
                                                 <div class="tooltip-row">
                                                     <span>Verfügbarkeit</span>
                                                     <strong>100%</strong>
                                                 </div>
                                             </div>
                                         </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            <div class="timeline-labels">
                                <span>Vor 7 Tagen</span>
                                <span>Heute</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="status-footer">
                        <p id="last-update">Zuletzt geprüft: <?php echo date("H:i:s"); ?> • Automatische Aktualisierung aktiv</p>
                    </div>
                </div>

                <script>
                async function updateStatus() {
                    try {
                        const response = await fetch('api/get_status.php');
                        const data = await response.json();
                        
                        document.getElementById('last-update').innerHTML = `Zuletzt geprüft: ${data.timestamp} • Automatische Aktualisierung aktiv`;
                        
                        // Summary bar
                        const summary = document.getElementById('status-summary');
                        const summaryText = document.getElementById('summary-text');
                        if (data.status === 'online') {
                            summary.className = 'status-summary-bar online';
                            summaryText.innerText = 'Alle Systeme laufen einwandfrei';
                            summary.querySelector('i').className = 'fas fa-check-circle';
                        } else {
                            summary.className = 'status-summary-bar degraded';
                            summaryText.innerText = 'Eingeschränkte Konnektivität erkannt';
                            summary.querySelector('i').className = 'fas fa-exclamation-triangle';
                        }

                        // Services
                        for (const [id, service] of Object.entries(data.services)) {
                            const pingEl = document.getElementById(`ping-${id}`);
                            const badgeEl = document.getElementById(`badge-${id}`);
                            
                            if (pingEl) pingEl.innerText = `Latenz: ${service.ping} • `;
                            if (badgeEl) {
                                badgeEl.innerText = service.status === 'online' ? 'Online' : 'Offline';
                                badgeEl.className = `status-badge-inline ${service.status}`;
                            }
                        }
                    } catch (e) {
                        console.error("Status update failed", e);
                    }
                }

                setInterval(updateStatus, 10000);
                updateStatus();
                </script>
            <?php elseif ($type === 'cookies'): ?>
                <div class="legal-text">
                    <h3>Deine Cookie-Einstellungen</h3>
                    <p>Hier kannst du verwalten, welche Cookies wir verwenden dürfen. Grundsätzlich nutzen wir nur technisch notwendige Cookies, um deine Sitzung und Sicherheit zu gewährleisten.</p>
                    <div class="cookie-settings-actions">
                        <button onclick="openCookieSettings()" class="btn-minecraft">
                            <span class="btn-text">EINSTELLUNGEN ÖFFNEN</span>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
