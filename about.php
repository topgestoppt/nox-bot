<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="css/minecraft-style.css">

<style>
    /* Custom enhancements for the About page */
    .about-hero {
        padding: 180px 0 100px;
        background: linear-gradient(rgba(5, 5, 16, 0.8), rgba(5, 5, 16, 0.9)), 
                    url('https://wallpapers.com/images/hd/minecraft-pictures-wc3wk7lnm3zulf7g.jpg') center/cover no-repeat;
        position: relative;
        overflow: hidden;
        text-align: center;
    }

    .about-hero-content {
        position: relative;
        z-index: 2;
        max-width: 900px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .pixel-title {
        font-family: 'VT323', monospace;
        font-size: 5rem;
        margin-bottom: 20px;
        background: linear-gradient(to bottom, #fff 0%, var(--primary-purple) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        filter: drop-shadow(0 0 15px rgba(125, 64, 255, 0.4));
        animation: fadeInUp 1s ease-out;
    }

    .about-subtitle {
        font-size: 1.5rem;
        color: var(--text-muted);
        margin-bottom: 40px;
        animation: fadeInUp 1s ease-out 0.2s both;
    }

    .section-divider {
        height: 100px;
        background: linear-gradient(to bottom, transparent, var(--bg-color));
        margin-top: -100px;
        position: relative;
        z-index: 1;
    }

    .modern-section {
        padding: 100px 0;
        position: relative;
    }

    .modern-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        align-items: center;
    }

    .content-image {
        position: relative;
        border-radius: 24px;
        overflow: hidden;
        border: 1px solid var(--glass-border);
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.5);
        transition: transform 0.5s cubic-bezier(0.165, 0.84, 0.44, 1);
    }

    .content-image:hover {
        transform: scale(1.02) translateY(-10px);
    }

    .content-image img {
        width: 100%;
        display: block;
    }

    .content-image::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(45deg, rgba(125, 64, 255, 0.2), transparent);
        pointer-events: none;
    }

    .character-image-container {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        position: relative;
        margin-top: -30px; /* Pull it higher */
    }

    .history-character {
        max-width: 110%;
        height: auto;
        max-height: 550px;
        object-fit: contain;
        z-index: 2;
        transition: all 0.6s cubic-bezier(0.165, 0.84, 0.44, 1);
        /* Glowing outline around the skin pixels, not the box */
        filter: drop-shadow(0 0 5px var(--primary-purple))
                drop-shadow(0 0 15px rgba(125, 64, 255, 0.4));
        animation: floatSkin 6s ease-in-out infinite;
    }

    .history-character:hover {
        transform: scale(1.05) rotate(2deg);
        filter: drop-shadow(0 0 8px var(--primary-purple))
                drop-shadow(0 0 25px rgba(125, 64, 255, 0.8));
    }

    @keyframes floatSkin {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-20px); }
    }

    .philosophy-character {
        max-width: 110%;
        height: auto;
        max-height: 550px;
        object-fit: contain;
        z-index: 2;
        transition: all 0.6s cubic-bezier(0.165, 0.84, 0.44, 1);
        /* Glowing outline with a hint of cyan to match the eyes */
        filter: drop-shadow(0 0 5px #00f2ff)
                drop-shadow(0 0 15px rgba(125, 64, 255, 0.4));
        animation: floatSkin 6s ease-in-out infinite reverse; /* Reverse animation for variety */
    }

    .philosophy-character:hover {
        transform: scale(1.05) rotate(-2deg);
        filter: drop-shadow(0 0 8px #00f2ff)
                drop-shadow(0 0 25px rgba(125, 64, 255, 0.8));
    }

    .feature-list {
        list-style: none;
        margin-top: 30px;
    }

    .feature-list li {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
        font-size: 1.1rem;
        color: var(--text-white);
    }

    .feature-list li i {
        color: var(--primary-purple);
        font-size: 1.2rem;
        width: 25px;
        text-align: center;
    }

    /* Enhanced Team Cards */
    .team-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
        margin-top: 50px;
    }

    .team-card {
        background: rgba(125, 64, 255, 0.03);
        border: 1px solid var(--glass-border);
        border-radius: 24px;
        padding: 40px;
        text-align: center;
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        position: relative;
        overflow: hidden;
    }

    .team-card::before {
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

    .team-card:hover {
        transform: translateY(-15px);
        background: rgba(125, 64, 255, 0.08);
        border-color: var(--primary-purple);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 0 0 20px rgba(125, 64, 255, 0.2);
    }

    .team-card:hover::before {
        opacity: 1;
    }

    .team-icon {
        font-size: 3.5rem;
        color: var(--primary-purple);
        margin-bottom: 25px;
        filter: drop-shadow(0 0 10px rgba(125, 64, 255, 0.3));
        transition: transform 0.4s ease;
    }

    .team-card:hover .team-icon {
        transform: scale(1.1) rotate(5deg);
    }

    .team-card h3 {
        font-family: 'VT323', monospace;
        font-size: 2.2rem;
        margin-bottom: 15px;
        color: white;
    }

    /* Service Cards Modern */
    .service-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 25px;
        margin-top: 50px;
    }

    .service-card {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--glass-border);
        border-radius: 20px;
        padding: 30px;
        transition: all 0.3s ease;
    }

    .service-card:hover {
        background: rgba(125, 64, 255, 0.05);
        border-color: rgba(125, 64, 255, 0.3);
        transform: translateY(-5px);
    }

    .service-icon {
        width: 50px;
        height: 50px;
        background: rgba(125, 64, 255, 0.1);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-purple);
        font-size: 1.5rem;
        margin-bottom: 20px;
    }

    /* Data reveal animation integration */
    [data-reveal] {
        opacity: 0;
        transform: translateY(30px);
        transition: all 0.8s cubic-bezier(0.165, 0.84, 0.44, 1);
    }

    [data-reveal].active {
        opacity: 1;
        transform: translateY(0);
    }

    @media (max-width: 992px) {
        .modern-grid {
            grid-template-columns: 1fr;
            text-align: center;
        }
        .modern-grid > div:first-child {
            order: 2;
        }
        .content-image {
            order: 1;
            margin-bottom: 40px;
        }
        .team-grid {
            grid-template-columns: 1fr;
        }
        .pixel-title {
            font-size: 3.5rem;
        }
    }
</style>

<section class="about-hero">
    <div class="hero-bg-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    <div class="about-hero-content">
        <span class="hero-welcome">Erfahre mehr</span>
        <h1 class="pixel-title">Über Noxus</h1>
        <p class="about-subtitle">
            Die Geschichte eines Clans, der aus Leidenschaft und Zusammenhalt geboren wurde.
        </p>
        <div class="hero-stats" style="margin-top: 20px;">
            <div class="stat-item" style="--delay: 0.1s">
                <span class="stat-value">2026</span>
                <span class="stat-label">Gegründet</span>
            </div>
            <div class="stat-item" style="--delay: 0.2s">
                <span class="stat-value">100%</span>
                <span class="stat-label">Loyalität</span>
            </div>
            <div class="stat-item" style="--delay: 0.3s">
                <span class="stat-value">OP</span>
                <span class="stat-label">Equipment</span>
            </div>
        </div>
    </div>
</section>

<div class="section-divider"></div>

<section class="modern-section" data-reveal>
    <div class="container">
        <div class="modern-grid">
            <div>
                <h2 class="section-title" style="text-align: left; margin: 0;">Unsere Geschichte</h2>
                <h3 class="section-subtitle" style="text-align: left; margin-top: 10px;">Vom ersten Block zum Imperium</h3>
                <div class="mc-text" style="margin-top: 25px;">
                    <p>Es begann alles in einer kleinen Welt aus Blöcken... Eine Gruppe von Visionären erkannte, dass wahre Stärke nicht in den Items liegt, die man besitzt, sondern in den Menschen, mit denen man spielt.</p>
                    <p><strong>Der Noxus [NOX]</strong> wurde gegründet, um Spielern eine einzigartige Erfahrung auf <strong>OPSUCHT</strong> zu bieten. Was als kleine Gruppe von Freunden startete, hat sich zu einem der strukturiertesten und aktivsten Clans entwickelt.</p>
                    <p>Wir legen Wert auf <strong>Zusammenhalt, Ehrlichkeit und gemeinsamen Fortschritt</strong>. Bei uns ist niemand nur eine Nummer – wir sind ein Kollektiv.</p>
                </div>
                <ul class="feature-list">
                    <li><i class="fas fa-check-circle"></i> Täglich aktive Member</li>
                    <li><i class="fas fa-check-circle"></i> Professionelle Clan-Struktur</li>
                    <li><i class="fas fa-check-circle"></i> Fokus auf Fairplay</li>
                    <li><i class="fas fa-check-circle"></i> Ständige Weiterentwicklung</li>
                </ul>
            </div>
            <div class="character-image-container">
                <img src="assets/img/image-20260525213658-2djrrn..png" alt="Noxus Character" class="history-character">
            </div>
        </div>
    </div>
</section>

<section class="modern-section" style="background: rgba(125, 64, 255, 0.02);" data-reveal>
    <div class="container">
        <h2 class="section-title">Unser Team</h2>
        <h3 class="section-subtitle">Die Säulen unserer Gemeinschaft</h3>
        <div class="team-grid">
            <div class="team-card">
                <div class="team-icon"><i class="fas fa-crown"></i></div>
                <h3>NOX-URVATER</h3>
                <p>Die Schöpfer und Leiter von Noxus. Sie sorgen für die strategische Ausrichtung und das Wachstum des Clans.</p>
            </div>
            <div class="team-card">
                <div class="team-icon"><i class="fas fa-shield-alt"></i></div>
                <h3>NOX-VIZE</h3>
                <p>Unsere Hüter der Regeln. Sie unterstützen die Mitglieder und sorgen für ein faires Miteinander.</p>
            </div>
            <div class="team-card">
                <div class="team-icon"><i class="fas fa-hammer"></i></div>
                <h3>NOX-KOLLEKTIV</h3>
                <p>Das Herzstück. Sie sorgen für Zusammenhalt, Events und eine freundliche Atmosphäre für alle.</p>
            </div>
        </div>
    </div>
</section>

<section class="modern-section" data-reveal>
    <div class="container">
        <h2 class="section-title">Unsere Services</h2>
        <h3 class="section-subtitle">Was wir der Community bieten</h3>
        <div class="service-grid">
            <div class="service-card">
                <div class="service-icon"><i class="fas fa-coins"></i></div>
                <h3>Geld-Verleih</h3>
                <p>Wir unterstützen dich bei deinen Projekten mit dem nötigen Startkapital auf OPSUCHT.NET.</p>
            </div>
            <div class="service-card">
                <div class="service-icon"><i class="fas fa-box-open"></i></div>
                <h3>Item-Verleih</h3>
                <p>Benötigst du kurzzeitig wertvolle Ausrüstung? Wir helfen unkompliziert aus.</p>
            </div>
            <div class="service-card">
                <div class="service-icon"><i class="fas fa-shopping-cart"></i></div>
                <h3>Item-Verkauf</h3>
                <p>Verkaufe deine OP-Items sicher und zu fairen Konditionen direkt an uns.</p>
            </div>
            <div class="service-card">
                <div class="service-icon"><i class="fas fa-map-marked-alt"></i></div>
                <h3>Grundstück-Service</h3>
                <p>An- und Verkauf von Grundstücken mit sicherer Abwicklung und fairen Preisen.</p>
            </div>
            <div class="service-card">
                <div class="service-icon"><i class="fas fa-code"></i></div>
                <h3>Code-Auftrag</h3>
                <p>Individuelle Programmier-Wünsche, professionell in Code umgesetzt.</p>
            </div>
            <div class="service-card" style="border-color: var(--primary-purple); background: rgba(125, 64, 255, 0.05);">
                <div class="service-icon"><i class="fas fa-rocket"></i></div>
                <h3>Noxus-Bonus</h3>
                <ul style="list-style: none; padding: 0; font-size: 0.9rem; color: var(--text-muted);">
                    <li><i class="fas fa-bolt" style="color: #ffcc00; margin-right: 5px;"></i> Schnelle Bearbeitung</li>
                    <li><i class="fas fa-lock" style="color: #55ff55; margin-right: 5px;"></i> 100% Sicher</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="modern-section" style="padding-bottom: 150px;" data-reveal>
    <div class="container">
        <div class="modern-grid">
            <div class="character-image-container">
                <img src="assets/img/shadow-skin.png" alt="Noxus Philosophy Character" class="philosophy-character">
            </div>
            <div>
                <h2 class="section-title" style="text-align: left; margin: 0;">Unsere Philosophie</h2>
                <h3 class="section-subtitle" style="text-align: left; margin-top: 10px;">Schattenkinder des Erfolgs</h3>
                <div class="mc-text" style="margin-top: 25px;">
                    <p>Bei uns stehen unsere Schattenkinder (Mitglieder) im Vordergrund. Wir glauben daran, dass man nur gemeinsam wirklich groß werden kann. Jeder Block, den wir setzen, bringt uns als Kollektiv weiter.</p>
                    <p>Wir bieten unseren Mitgliedern nicht nur einen Platz zum Spielen, sondern ein Zuhause mit Perspektive und echten Aufstiegsmöglichkeiten innerhalb unserer Hierarchie.</p>
                </div>
                <div style="margin-top: 40px;">
                    <a href="https://dsc.gg/noxclan" target="_blank" class="btn-minecraft">
                        <span class="btn-text">Werde Teil des Clans</span>
                        <span class="btn-subtext">Join Discord</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
