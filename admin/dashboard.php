<?php
session_start();
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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <div>
            <span>SDS Admin</span><br>
            <small>Sen Digital Solution</small>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Principal</div>
        <button class="nav-item active" data-page="overview">
            <span class="nav-icon">📊</span> Vue d'ensemble
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
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?= mb_substr($admin['display_name'], 0, 1) ?></div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($admin['display_name']) ?></div>
                <div class="sidebar-user-role"><?= htmlspecialchars($admin['role']) ?></div>
            </div>
        </div>
    </div>
</aside>

<!-- MAIN -->
<main class="main">
    <header class="header">
        <button class="menu-toggle-admin" id="admin-menu-toggle">☰</button>
        <h2 id="header-title">📊 Vue d'ensemble</h2>
        <div class="header-actions">
            <a href="../index.html" target="_blank" class="header-btn">🌐 Voir le site</a>
            <a href="logout.php" class="header-btn">🚪 Déconnexion</a>
        </div>
    </header>

    <div class="content" id="page-content">
        <div class="page-loader"><div class="spinner"></div></div>
    </div>
</main>

<!-- TOASTS -->
<div class="toast-container" id="toast-container"></div>

<script src="assets/admin.js"></script>
</body>
</html>
