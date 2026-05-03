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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --bg: #0A0A0F;
            --surface: #12121A;
            --card: #1A1A24;
            --border: #2A2A3A;
            --text: #E8E8ED;
            --text-dim: #8888A0;
            --accent: #6C63FF;
            --gold: #D4AF37;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .welcome {
            text-align: center;
            animation: fadeIn 0.5s ease;
        }
        .welcome h1 {
            font-size: 2rem;
            margin-bottom: 12px;
            background: linear-gradient(135deg, var(--accent), var(--gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .welcome p { color: var(--text-dim); margin-bottom: 24px; }
        .welcome .badge {
            display: inline-block;
            padding: 6px 16px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            font-size: 0.85rem;
            color: var(--gold);
            margin-bottom: 24px;
        }
        .logout-btn {
            padding: 12px 28px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text);
            font-family: inherit;
            font-size: 0.9rem;
            cursor: pointer;
            transition: border-color 0.3s;
        }
        .logout-btn:hover { border-color: var(--accent); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; } }
    </style>
</head>
<body>
    <div class="welcome">
        <div class="badge">🔐 <?= htmlspecialchars($admin['role']) ?></div>
        <h1>Bienvenue, <?= htmlspecialchars($admin['display_name']) ?> 👋</h1>
        <p>Le dashboard complet arrive en Phase 2. L'authentification fonctionne !</p>
        <button class="logout-btn" onclick="logout()">Se déconnecter</button>
    </div>
    <script>
    async function logout() {
        await fetch('api/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'logout' })
        });
        window.location.href = 'index.php';
    }
    </script>
</body>
</html>
