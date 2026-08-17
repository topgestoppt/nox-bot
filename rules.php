<?php include 'includes/header.php'; ?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&display=swap');

    :root {
        --book-gold: #d4af37;
        --book-gold-bright: #f9e295;
        --book-accent: #7d40ff;
    }

    .rules-hero {
        padding: 180px 0 100px;
        background: linear-gradient(rgba(5, 5, 16, 0.8), rgba(5, 5, 16, 0.9)), 
                    url('https://wallpapers.com/images/hd/minecraft-pictures-wc3wk7lnm3zulf7g.jpg') center/cover no-repeat;
        position: relative;
        overflow: hidden;
        text-align: center;
        background-attachment: fixed;
    }

    .rules-hero-content {
        position: relative;
        z-index: 2;
        max-width: 900px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .pixel-title-rules {
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

    .rules-subtitle-hero {
        font-size: 1.4rem;
        color: var(--text-muted);
        margin-bottom: 40px;
        animation: fadeInUp 1s ease-out 0.2s both;
    }

    .rules-main-container {
        max-width: 1000px;
        margin: -50px auto 100px;
        position: relative;
        z-index: 5;
        padding: 0 20px;
    }

    .rules-glass-card {
        background: rgba(10, 10, 20, 0.4);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid var(--glass-border);
        border-radius: 30px;
        padding: 60px;
        box-shadow: 0 40px 100px rgba(0, 0, 0, 0.6);
    }

    .book-style-header {
        text-align: center;
        margin-bottom: 60px;
    }

    .book-style-header h2 {
        font-family: 'Cinzel', serif;
        font-size: 2.5rem;
        background: linear-gradient(to right, var(--book-gold), var(--book-gold-bright));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        text-transform: uppercase;
        letter-spacing: 4px;
        margin-bottom: 15px;
        filter: drop-shadow(0 0 10px rgba(212, 175, 55, 0.2));
    }

    .decoration-line {
        height: 2px;
        background: linear-gradient(90deg, transparent, var(--book-gold), transparent);
        margin: 30px auto;
        width: 80%;
        opacity: 0.5;
    }

    .rule-category {
        margin-bottom: 25px;
        border: 1px solid var(--glass-border);
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.02);
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        overflow: hidden;
    }

    .rule-category:hover {
        border-color: rgba(125, 64, 255, 0.3);
        background: rgba(125, 64, 255, 0.03);
    }

    .category-header {
        padding: 25px 35px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        user-select: none;
    }

    .category-title {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .category-title i {
        color: var(--book-gold);
        font-size: 1.5rem;
        filter: drop-shadow(0 0 5px rgba(212, 175, 55, 0.3));
    }

    .category-title h3 {
        margin: 0;
        font-family: 'Cinzel', serif;
        font-size: 1.3rem;
        color: #fff;
        letter-spacing: 1px;
    }

    .toggle-icon {
        transition: transform 0.5s cubic-bezier(0.165, 0.84, 0.44, 1);
        color: var(--text-muted);
    }

    .rule-category.active {
        border-color: var(--primary-purple);
        background: rgba(125, 64, 255, 0.05);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
    }

    .rule-category.active .toggle-icon {
        transform: rotate(180deg);
        color: var(--primary-purple);
    }

    .category-content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.6s cubic-bezier(0.165, 0.84, 0.44, 1);
    }

    .rule-category.active .category-content {
        max-height: 4000px;
    }

    .rule-item {
        padding: 30px 45px 30px 90px;
        border-top: 1px solid var(--glass-border);
        position: relative;
    }

    .rule-item::before {
        content: '§';
        position: absolute;
        left: 45px;
        top: 30px;
        color: var(--primary-purple);
        font-family: 'Cinzel', serif;
        font-weight: bold;
        font-size: 1.5rem;
        opacity: 0.7;
    }

    .rule-item h4 {
        color: var(--book-gold-bright);
        margin-bottom: 12px;
        font-size: 1.15rem;
        font-family: 'Cinzel', serif;
    }

    .rule-item p {
        color: var(--text-muted);
        line-height: 1.8;
        margin-bottom: 15px;
    }

    .rule-sub-list {
        list-style: none;
        padding-left: 0;
    }

    .rule-sub-list li {
        position: relative;
        padding-left: 25px;
        margin-bottom: 12px;
        color: var(--text-muted);
        line-height: 1.6;
    }

    .rule-sub-list li::before {
        content: '▹';
        position: absolute;
        left: 0;
        color: var(--primary-purple);
        font-weight: bold;
    }

    .rule-quote {
        border-left: 3px solid var(--book-gold);
        padding-left: 20px;
        margin: 20px 0;
        font-style: italic;
        color: var(--text-muted);
        background: rgba(212, 175, 55, 0.05);
        padding: 15px 20px;
    }

    .link-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin-top: 20px;
    }

    .link-item {
        color: var(--primary-purple);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s ease;
    }

    .link-item:hover {
        color: #fff;
        transform: translateX(10px);
    }

    @media (max-width: 768px) {
        .pixel-title-rules { font-size: 3.5rem; }
        .rules-glass-card { padding: 40px 20px; }
        .rule-item { padding-left: 60px; }
        .rule-item::before { left: 25px; }
        .category-header { padding: 20px; }
        .category-title h3 { font-size: 1.1rem; }
    }
</style>

<section class="rules-hero">
    <div class="hero-bg-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    <div class="rules-hero-content">
        <span class="hero-welcome">Der neue Kodex</span>
        <h1 class="pixel-title-rules">Regelwerk</h1>
        <p class="rules-subtitle-hero">
            Unsere aktualisierten Richtlinien für ein faires, respektvolles und erfolgreiches Miteinander.
        </p>
    </div>
</section>

<div class="rules-main-container">
    <div class="rules-glass-card" data-reveal>
        <div class="book-style-header">
            <h2>Noxus Gesetzbuch</h2>
            <div class="decoration-line"></div>
            <p style="color: var(--text-muted); font-style: italic; font-family: 'Cinzel', serif;">"Wer die Ordnung wahrt, wird den Sieg finden."</p>
        </div>

        <!-- Kategorie 1: Verhaltenskodex & Respekt -->
        <div class="rule-category">
            <div class="category-header" onclick="toggleCategory(this)">
                <div class="category-title">
                    <i class="fas fa-hand-holding-heart"></i>
                    <h3>1. Verhaltenskodex & Respekt</h3>
                </div>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="category-content">
                <div class="rule-item">
                    <h4>1.1 Grundsätzlicher Respekt</h4>
                    <p>Verhalte dich respektvoll gegenüber allen Mitgliedern. Ein höflicher und wertschätzender Ton ist die Basis unserer Gemeinschaft.</p>
                </div>
                <div class="rule-item">
                    <h4>1.2 Hierarchie & Autorität</h4>
                    <p>Gegenüber dem NOX-Kollektiv und der Clanleitung gilt ein besonders respektvoller Ton. Den Anweisungen der Leitung ist Folge zu leisten.</p>
                </div>
                <div class="rule-item">
                    <h4>1.3 Diskriminierungsverbot</h4>
                    <p>Beleidigungen, Rassismus, Sexismus oder Diskriminierung jeglicher Art sind strengstens untersagt und werden nicht toleriert.</p>
                </div>
                <div class="rule-item">
                    <h4>1.4 Sicherheit & Gewaltverbot</h4>
                    <p>Drohungen gegen Mitglieder – ob im Spiel oder im echten Leben – sind verboten. Das Nachstellen (Stalking) oder die Androhung von Straftaten führen zum sofortigen Ausschluss.</p>
                </div>
            </div>
        </div>

        <!-- Kategorie 2: Kommunikation & Inhalte -->
        <div class="rule-category">
            <div class="category-header" onclick="toggleCategory(this)">
                <div class="category-title">
                    <i class="fas fa-comments"></i>
                    <h3>2. Kommunikation & Inhalte</h3>
                </div>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="category-content">
                <div class="rule-item">
                    <h4>2.1 Sauberer Chat</h4>
                    <p>Spamming, unerlaubte Werbung und unangebrachte Inhalte sind untersagt. Bitte achte auf eine angemessene Ausdrucksweise.</p>
                </div>
                <div class="rule-item">
                    <h4>2.2 Jugendschutz & NSFW</h4>
                    <p>NSFW-Inhalte, sexuelle Andeutungen sowie anzügliche Sprüche, Emojis oder Bilder sind strikt verboten.</p>
                </div>
                <div class="rule-item">
                    <h4>2.3 Datenschutz & Sicherheit</h4>
                    <p>Die Offenlegung privater Daten (Doxing) anderer Mitglieder ist untersagt. Das Verbreiten schadhafter oder unerlaubter Links ist verboten.</p>
                </div>
                <div class="rule-item">
                    <h4>2.4 Clan-Interaktion</h4>
                    <p>Betteln nach Geld, Items oder Rängen im Clan-Chat ist nicht gestattet. Verfassungsfeindliche Aussagen oder Symbole sind absolut untersagt.</p>
                </div>
            </div>
        </div>

        <!-- Kategorie 3: Identität & Accounts -->
        <div class="rule-category">
            <div class="category-header" onclick="toggleCategory(this)">
                <div class="category-title">
                    <i class="fas fa-user-shield"></i>
                    <h3>3. Identität & Accounts</h3>
                </div>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="category-content">
                <div class="rule-item">
                    <h4>3.1 Authentizität</h4>
                    <p>Identitätsfälschung und das Erstellen von Fake-Accounts sind verboten. Jeder steht für seine eigene Identität ein.</p>
                </div>
                <div class="rule-item">
                    <h4>3.2 Namensgebung</h4>
                    <p>Discord- und Minecraft-Namen müssen angemessen sein und dürfen keine beleidigenden oder anstößigen Inhalte enthalten.</p>
                </div>
                <div class="rule-item">
                    <h4>3.3 Sanktionen</h4>
                    <p>Bestrafungen dürfen nicht umgangen werden (z. B. durch Twink-Accounts). Ein ehrlicher Umgang mit Sanktionen wird erwartet.</p>
                </div>
            </div>
        </div>

        <!-- Kategorie 4: Clan-Gesetze & Integrität -->
        <div class="rule-category">
            <div class="category-header" onclick="toggleCategory(this)">
                <div class="category-title">
                    <i class="fas fa-gavel"></i>
                    <h3>4. Clan-Gesetze & Integrität</h3>
                </div>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="category-content">
                <div class="rule-item">
                    <h4>4.1 Fairplay & Betrug</h4>
                    <p>Betrug oder Scamming an anderen Clan-Mitgliedern ist strengstens untersagt und führt zum permanenten Ausschluss.</p>
                </div>
                <div class="rule-item">
                    <h4>4.2 Missbrauch von Rechten</h4>
                    <p>Die Ausnutzung des eigenen Rangs oder von Sonderrechten ist verboten. Ebenso ist die missbräuchliche Nutzung des Supports zu unterlassen.</p>
                </div>
                <div class="rule-item">
                    <h4>4.3 Geistiges Eigentum</h4>
                    <p>Das Kopieren oder Entwenden von Designs, Ideen oder Systemen unseres Clans ist strengstens untersagt und wird konsequent geahndet.</p>
                </div>
                <div class="rule-item">
                    <h4>4.4 Konkurrenzschutz</h4>
                    <p>Services, die wir anbieten – ob kommerziell oder nicht – dürfen von Mitgliedern nicht eigenständig als Konkurrenzangebot betrieben werden.</p>
                </div>
            </div>
        </div>

        <!-- Kategorie 5: Die Truhe des Vertrauens (Item-Verleih) -->
        <div class="rule-category">
            <div class="category-header" onclick="toggleCategory(this)">
                <div class="category-title">
                    <i class="fas fa-box-open"></i>
                    <h3>5. Die Truhe des Vertrauens (Item-Verleih)</h3>
                </div>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="category-content">
                <div class="rule-item">
                    <div class="rule-quote">"Nur wer Ehre besitzt, darf Macht tragen. Wer diese Regeln bricht, verliert mehr als nur Items…"</div>
                    
                    <h4>🔐 Grundregeln des Verleihs</h4>
                    <ul class="rule-sub-list">
                        <li><strong>Vertrauen ist Pflicht:</strong> Geliehene Items gehören dir nicht – behandle sie mit höchster Sorgfalt.</li>
                        <li><strong>Rückgabe ist Gesetz:</strong> Jedes Item wird vollständig und rechtzeitig zurückgegeben.</li>
                        <li><strong>Keine Weitergabe:</strong> Verliehene Items dürfen NICHT an Dritte weitergegeben werden.</li>
                        <li><strong>Originalzustand:</strong> Items müssen im gleichen Zustand zurückgegeben werden (keine absichtliche Beschädigung!).</li>
                    </ul>
                </div>
                <div class="rule-item">
                    <h4>⏳ Leihdauer & Rückgabe</h4>
                    <ul class="rule-sub-list">
                        <li><strong>Standard-Leihzeit:</strong> Maximal 24 Stunden. Man darf mit geliehenen Items nicht offline gehen.</li>
                        <li><strong>Verlängerung:</strong> Nur nach vorheriger Absprache mit der Clan-Leaderschaft.</li>
                        <li><strong>Limitierung:</strong> Maximal 3 Items gleichzeitig pro Person.</li>
                        <li><strong>Verspätung:</strong> Führt zu Verleih-Sperren oder direkten Strafen.</li>
                    </ul>
                </div>
                <div class="rule-item">
                    <h4>⚖️ Verantwortung & Haftung</h4>
                    <ul class="rule-sub-list">
                        <li><strong>Verlust:</strong> Verlorene Items müssen absolut gleichwertig ersetzt werden.</li>
                        <li><strong>Diebstahl:</strong> Wer betrügt, wird sofort und dauerhaft vom System ausgeschlossen.</li>
                        <li><strong>Missbrauch:</strong> Nutzung gegen Clan-Regeln führt zu schweren Konsequenzen.</li>
                    </ul>
                </div>
                <div class="rule-item">
                    <h4>📋 Der Leihprozess</h4>
                    <ul class="rule-sub-list">
                        <li><strong>Anfrage:</strong> Melde dich bei der Clan-Leaderschaft (beim ersten Mal).</li>
                        <li><strong>Dokumentation:</strong> Jeder Verleih wird durch ein Ticket im Item-Slot dokumentiert.</li>
                        <li><strong>Bestätigung:</strong> Einmalige Bestätigung der Regeln vor dem ersten Verleih.</li>
                        <li><strong>Abschluss:</strong> Die Rückgabe erfolgt durch den Austausch des Tickets gegen das Item.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Kategorie 6: Externe Richtlinien & In-Game -->
        <div class="rule-category">
            <div class="category-header" onclick="toggleCategory(this)">
                <div class="category-title">
                    <i class="fas fa-external-link-alt"></i>
                    <h3>6. Externe Richtlinien & In-Game</h3>
                </div>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="category-content">
                <div class="rule-item">
                    <h4>6.1 Discord & Plattformen</h4>
                    <p>Zusätzlich gelten die offiziellen Discord Community-Richtlinien und Nutzungsbedingungen.</p>
                    <div class="link-list">
                        <a href="https://discord.com/guidelines" target="_blank" class="link-item"><i class="fab fa-discord"></i> Discord Community-Richtlinien</a>
                        <a href="https://discord.com/terms" target="_blank" class="link-item"><i class="fas fa-file-alt"></i> Discord Nutzungsbedingungen</a>
                    </div>
                </div>
                <div class="rule-item">
                    <h4>6.2 OPSucht In-Game Regeln</h4>
                    <p>Es gelten die offiziellen Regeln von OPSucht. Jeder Spieler ist selbst dafür verantwortlich, diese zu kennen.</p>
                    <ul class="rule-sub-list">
                        <li>Keine Nutzung von Bugs oder Exploits.</li>
                        <li>Kein gezieltes Stören von Aufnahmen oder Streams (Content-Sniping).</li>
                        <li>Verstöße werden direkt vom OPSucht-Team geahndet.</li>
                    </ul>
                    <a href="https://wiki.opsucht.net/regelwerk" target="_blank" class="link-item" style="margin-top: 15px;"><i class="fas fa-book"></i> OPSucht Wiki - Regelwerk</a>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 40px;">
            <a href="index.php" class="btn-minecraft secondary">
                <span class="btn-text">Akzeptieren & Zurück</span>
                <span class="btn-subtext">Spawn</span>
            </a>
        </div>
    </div>
</div>

<script>
    function toggleCategory(header) {
        const category = header.parentElement;
        const isActive = category.classList.contains('active');
        
        // Close all other categories
        document.querySelectorAll('.rule-category').forEach(cat => {
            cat.classList.remove('active');
        });
        
        // Toggle current
        if (!isActive) {
            category.classList.add('active');
        }
    }

    // Reveal animation integration
    document.addEventListener('DOMContentLoaded', function() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: "0px 0px -50px 0px"
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, observerOptions);

        document.querySelectorAll('[data-reveal]').forEach(el => {
            observer.observe(el);
        });
    });
</script>

<?php include 'includes/footer.php'; ?>
