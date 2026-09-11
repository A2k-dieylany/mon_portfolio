<?php
require_once __DIR__ . '/includes/auth_check.php';
require_auth();
$admin = get_admin();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SDS Admin — Dashboard</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= sds_asset('admin/assets/admin.css') ?>">
    <link rel="stylesheet" href="<?= sds_asset('admin/assets/admin-premium.css') ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <style>
        body { font-family: 'Inter', 'Outfit', system-ui, sans-serif; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div>
            <span>SDS</span> <span style="-webkit-text-fill-color:var(--text);font-size:1.4rem">Admin</span><br>
            <small>Sen Digital Solution</small>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Principal</div>
        <button class="nav-item active" data-page="overview">
            <span class="nav-icon">📊</span> Vue d'ensemble
        </button>
        <button class="nav-item" data-page="crm">
            <span class="nav-icon">🤝</span> Prospects &amp; Clients
        </button>
        <button class="nav-item" data-page="messages">
            <span class="nav-icon">💬</span> Messages
            <span class="nav-badge" id="msg-badge" style="display:none">0</span>
        </button>

        <div class="nav-section">Contenu</div>
        <button class="nav-item" data-page="projects">
            <span class="nav-icon">🚀</span> Projets
        </button>
        <button class="nav-item" data-page="services">
            <span class="nav-icon">⚙️</span> Services
        </button>
        <button class="nav-item" data-page="skills">
            <span class="nav-icon">🧠</span> Compétences
        </button>
        <button class="nav-item" data-page="blog">
            <span class="nav-icon">✍️</span> Blog
        </button>
        <button class="nav-item" data-page="testimonials">
            <span class="nav-icon">⭐</span> Témoignages
        </button>
        <button class="nav-item" data-page="timeline">
            <span class="nav-icon">📅</span> Timeline
        </button>

        <div class="nav-section">Analytics</div>
        <button class="nav-item" data-page="analytics">
            <span class="nav-icon">👁️</span> Visiteurs
        </button>
        <button class="nav-item" data-page="chatbot">
            <span class="nav-icon">🤖</span> Chatbot
        </button>

        <div class="nav-section">Système</div>
        <button class="nav-item" data-page="appearance">
            <span class="nav-icon">🎨</span> Apparence
        </button>
        <button class="nav-item" data-page="settings">
            <span class="nav-icon">🔧</span> Paramètres
        </button>
        <button class="nav-item" data-page="account">
            <span class="nav-icon">🔑</span> Mon compte
        </button>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?= htmlspecialchars(mb_substr($admin['display_name'], 0, 1)) ?></div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($admin['display_name']) ?></div>
                <div class="sidebar-user-role" style="display:flex;align-items:center;gap:4px">
                    <span style="width:6px;height:6px;background:var(--green);border-radius:50%;display:inline-block;box-shadow:0 0 6px var(--green)"></span>
                    <?= htmlspecialchars($admin['role']) ?>
                </div>
            </div>
        </div>
    </div>
</aside>

<!-- OVERLAY mobile -->
<div id="sidebar-overlay" style="position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:99;backdrop-filter:blur(4px)"></div>

<!-- MAIN -->
<main class="main">
    <header class="header">
        <div style="display:flex;align-items:center;gap:12px">
            <button class="menu-toggle-admin" id="admin-menu-toggle"
                    aria-label="Ouvrir le menu" aria-controls="sidebar" aria-expanded="false">☰</button>
            <h2 id="header-title" class="header-title">📊 Vue d'ensemble</h2>
        </div>
        <div class="header-actions">
            <span style="font-size:0.75rem;color:var(--text-muted);display:none" id="clock"></span>
            <a href="../index.php" target="_blank" class="header-btn" aria-label="Voir le site">🌐 <span class="btn-label">Voir le site</span></a>
            <form method="post" action="logout.php" style="display:contents">
                <button type="submit" class="header-btn" aria-label="Déconnexion" style="border-color:rgba(251,113,133,0.2);color:var(--red)">🚪 <span class="btn-label">Déconnexion</span></button>
            </form>
        </div>
    </header>

    <div class="content" id="page-content">
        <div class="page-loader"><div class="spinner"></div></div>
    </div>
</main>

<!-- TOASTS -->
<div class="toast-container" id="toast-container"></div>

<script src="<?= sds_asset('admin/assets/admin.js') ?>"></script>
<script>
// Live clock in header
(function updateClock() {
    const el = document.getElementById('clock');
    if (el) {
        const now = new Date();
        el.textContent = now.toLocaleDateString('fr-FR', {weekday:'short', day:'numeric', month:'short'}) 
            + ' · ' + now.toLocaleTimeString('fr-FR', {hour:'2-digit', minute:'2-digit'});
        el.style.display = 'inline';
    }
    setTimeout(updateClock, 30000);
})();

// Le menu mobile est géré par Admin.bindMobileMenu() dans admin.js. Un second
// gestionnaire ici faisait basculer le menu deux fois par appui : il se
// refermait aussitôt et seul le voile sombre restait affiché.
</script>
</body>
</html>
