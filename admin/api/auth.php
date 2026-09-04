<?php
/**
 * SDS Admin — API Authentification
 * Endpoints : login / logout / session
 * 
 * POST { action: "login", username: "...", password: "..." }
 * POST { action: "logout" }
 * GET  → retourne l'état de la session
 */

require_once __DIR__ . '/../../session_bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

sds_session_start();

security_headers();

// ===== GET : Vérifier la session =====
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_SESSION['admin_id'])) {
        json_response([
            'authenticated' => true,
            'user' => [
                'id'           => $_SESSION['admin_id'],
                'username'     => $_SESSION['admin_username'],
                'display_name' => $_SESSION['admin_display_name'],
                'role'         => $_SESSION['admin_role'],
            ]
        ]);
    }
    json_response(['authenticated' => false]);
}

// ===== POST uniquement =====
require_post();
$data   = get_json_body();
$action = $data['action'] ?? '';

// ===== LOGOUT =====
if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
    json_response(['success' => true, 'message' => 'Déconnexion réussie.']);
}

// ===== LOGIN =====
if ($action !== 'login') {
    json_response(['error' => 'Action non reconnue.'], 400);
}

require_fields($data, ['username', 'password']);

$username = trim($data['username']);
$password = $data['password'];

// Validation basique
if (mb_strlen($username) > 50 || mb_strlen($password) > 255) {
    json_response(['error' => 'Identifiants invalides.'], 400);
}

try {
    $pdo = getDB();

    // Récupérer l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    // Utilisateur introuvable
    if (!$user) {
        json_response(['error' => 'Identifiants incorrects.'], 401);
    }

    // Vérifier le verrouillage (brute force)
    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
        $remaining = ceil((strtotime($user['locked_until']) - time()) / 60);
        json_response([
            'error' => "Compte verrouillé. Réessayez dans {$remaining} minute(s)."
        ], 429);
    }

    // Vérifier le mot de passe
    if (!password_verify($password, $user['password_hash'])) {
        // Incrémenter les tentatives
        $attempts = $user['login_attempts'] + 1;
        $lockUntil = null;

        // Verrouiller après 5 échecs (30 minutes)
        if ($attempts >= 5) {
            $lockUntil = date('Y-m-d H:i:s', time() + 1800);
            $attempts = 0; // Reset après verrouillage
        }

        $stmt = $pdo->prepare("UPDATE admin_users SET login_attempts = :attempts, locked_until = :lock WHERE id = :id");
        $stmt->execute([
            ':attempts' => $attempts,
            ':lock'     => $lockUntil,
            ':id'       => $user['id'],
        ]);

        if ($lockUntil) {
            json_response(['error' => 'Trop de tentatives. Compte verrouillé pour 30 minutes.'], 429);
        }

        json_response(['error' => 'Identifiants incorrects.'], 401);
    }

    // ===== SUCCÈS : Créer la session =====
    
    // Régénérer l'ID de session pour prévenir la fixation
    session_regenerate_id(true);

    $_SESSION['admin_id']           = $user['id'];
    $_SESSION['admin_username']     = $user['username'];
    $_SESSION['admin_display_name'] = $user['display_name'];
    $_SESSION['admin_role']         = $user['role'];
    $_SESSION['last_regeneration']  = time();

    // Reset tentatives + mettre à jour last_login
    $stmt = $pdo->prepare("UPDATE admin_users SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = :id");
    $stmt->execute([':id' => $user['id']]);

    json_response([
        'success' => true,
        'message' => 'Connexion réussie.',
        'user' => [
            'id'           => $user['id'],
            'username'     => $user['username'],
            'display_name' => $user['display_name'],
            'role'         => $user['role'],
        ]
    ]);

} catch (PDOException $e) {
    error_log("SDS Admin Auth Error: " . $e->getMessage());
    json_response(['error' => 'Erreur interne du serveur.'], 500);
}
