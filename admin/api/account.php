<?php
/**
 * SDS Admin API — Compte de l'administrateur connecté
 *
 * POST { action: "change_password", current_password, new_password }
 *
 * L'ancien mot de passe est exigé : c'est ce qui empêche un tiers de changer
 * le mot de passe depuis une session ouverte laissée sans surveillance, et ce
 * qui protège l'endpoint en l'absence de jeton CSRF côté admin.
 */
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

require_auth();
security_headers();
require_post();

$data   = get_json_body();
$action = $data['action'] ?? '';

if ($action !== 'change_password') {
    json_response(['error' => 'Action non reconnue.'], 400);
}

require_fields($data, ['current_password', 'new_password']);

$current = (string) $data['current_password'];
$new     = (string) $data['new_password'];

if (mb_strlen($new) < 10) {
    json_response(['error' => 'Le nouveau mot de passe doit faire au moins 10 caractères.'], 422);
}
if (mb_strlen($new) > 255) {
    json_response(['error' => 'Mot de passe trop long.'], 422);
}
if ($new === $current) {
    json_response(['error' => 'Le nouveau mot de passe doit être différent de l\'ancien.'], 422);
}

try {
    $pdo   = getDB();
    $admin = get_admin();

    $stmt = $pdo->prepare("SELECT id, password_hash FROM admin_users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $admin['id']]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($current, $user['password_hash'])) {
        json_response(['error' => 'Mot de passe actuel incorrect.'], 401);
    }

    $stmt = $pdo->prepare(
        "UPDATE admin_users
            SET password_hash = :hash, login_attempts = 0, locked_until = NULL
          WHERE id = :id"
    );
    $stmt->execute([
        ':hash' => password_hash($new, PASSWORD_DEFAULT),
        ':id'   => $user['id'],
    ]);

    json_response(['success' => true, 'message' => 'Mot de passe modifié.']);
} catch (Throwable $e) {
    error_log('Changement de mot de passe : ' . $e->getMessage());
    json_response(['error' => 'Erreur serveur. Réessayez.'], 500);
}
