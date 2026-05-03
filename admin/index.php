<?php
session_start();
// Si déjà connecté, rediriger vers le dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SDS Admin — Connexion</title>
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
            --accent-glow: rgba(108, 99, 255, 0.3);
            --gold: #D4AF37;
            --red: #FF4D6A;
            --green: #00D68F;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Fond animé */
        .bg-grid {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(108, 99, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(108, 99, 255, 0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            z-index: 0;
        }
        .bg-glow {
            position: fixed;
            width: 500px; height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, var(--accent-glow) 0%, transparent 70%);
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            z-index: 0;
            animation: pulse-glow 4s ease-in-out infinite;
        }
        @keyframes pulse-glow {
            0%, 100% { opacity: 0.3; transform: translate(-50%, -50%) scale(1); }
            50% { opacity: 0.5; transform: translate(-50%, -50%) scale(1.1); }
        }

        /* Card de login */
        .login-card {
            position: relative;
            z-index: 1;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 48px 40px;
            width: 420px;
            max-width: 92vw;
            backdrop-filter: blur(20px);
            box-shadow: 0 25px 60px rgba(0,0,0,0.5);
        }

        .login-logo {
            text-align: center;
            margin-bottom: 8px;
        }
        .login-logo span {
            font-size: 2.4rem;
            font-weight: 800;
            letter-spacing: -1px;
            background: linear-gradient(135deg, var(--accent), var(--gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .login-subtitle {
            text-align: center;
            color: var(--text-dim);
            font-size: 0.9rem;
            margin-bottom: 36px;
        }
        .login-subtitle strong {
            color: var(--gold);
        }

        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--text-dim);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text);
            font-family: inherit;
            font-size: 0.95rem;
            transition: border-color 0.3s, box-shadow 0.3s;
            outline: none;
        }
        .form-group input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }
        .form-group input::placeholder {
            color: var(--text-dim);
            opacity: 0.5;
        }

        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--accent), #8B83FF);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.3s;
            margin-top: 8px;
            position: relative;
            overflow: hidden;
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px var(--accent-glow);
        }
        .login-btn:active {
            transform: translateY(0);
        }
        .login-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .login-btn .spinner {
            display: none;
            width: 20px; height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin: 0 auto;
        }
        .login-btn.loading .btn-text { display: none; }
        .login-btn.loading .spinner { display: block; }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Messages d'erreur/succès */
        .login-message {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            display: none;
            text-align: center;
            font-weight: 500;
        }
        .login-message.error {
            display: block;
            background: rgba(255, 77, 106, 0.1);
            border: 1px solid rgba(255, 77, 106, 0.3);
            color: var(--red);
        }
        .login-message.success {
            display: block;
            background: rgba(0, 214, 143, 0.1);
            border: 1px solid rgba(0, 214, 143, 0.3);
            color: var(--green);
        }

        .login-footer {
            text-align: center;
            margin-top: 28px;
            font-size: 0.78rem;
            color: var(--text-dim);
            opacity: 0.6;
        }

        /* Animation d'entrée */
        .login-card {
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-card { padding: 36px 24px; }
            .login-logo span { font-size: 2rem; }
        }
    </style>
</head>
<body>
    <div class="bg-grid"></div>
    <div class="bg-glow"></div>

    <div class="login-card">
        <div class="login-logo"><span>SDS Admin</span></div>
        <p class="login-subtitle">Espace d'administration · <strong>SEN DIGITAL SOLUTION</strong></p>

        <div class="login-message" id="login-message"></div>

        <form id="login-form" autocomplete="off">
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" placeholder="Entrez votre identifiant" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" placeholder="Entrez votre mot de passe" required>
            </div>
            <button type="submit" class="login-btn" id="login-btn">
                <span class="btn-text">Se connecter →</span>
                <div class="spinner"></div>
            </button>
        </form>

        <div class="login-footer">© 2025 SEN DIGITAL SOLUTION — Accès restreint</div>
    </div>

    <script>
    const form = document.getElementById('login-form');
    const btn  = document.getElementById('login-btn');
    const msg  = document.getElementById('login-message');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        msg.className = 'login-message';
        msg.style.display = 'none';

        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;

        if (!username || !password) {
            showMessage('Veuillez remplir tous les champs.', 'error');
            return;
        }

        btn.classList.add('loading');
        btn.disabled = true;

        try {
            const res = await fetch('api/auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'login', username, password })
            });

            const data = await res.json();

            if (data.success) {
                showMessage('Connexion réussie ! Redirection...', 'success');
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 800);
            } else {
                showMessage(data.error || 'Erreur de connexion.', 'error');
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        } catch (err) {
            showMessage('Erreur réseau. Vérifiez votre connexion.', 'error');
            btn.classList.remove('loading');
            btn.disabled = false;
        }
    });

    function showMessage(text, type) {
        msg.textContent = text;
        msg.className = 'login-message ' + type;
        msg.style.display = 'block';
    }
    </script>
</body>
</html>
