<?php
/**
 * SDS Admin — Middleware d'authentification
 * Vérifie que l'utilisateur est connecté avant d'accéder aux pages/API admin
 */

function require_auth(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Vérifier la session admin
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_role'])) {
        // Déterminer si c'est un appel API (JSON) ou une page
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $accept      = $_SERVER['HTTP_ACCEPT'] ?? '';
        $isApi       = str_contains($contentType, 'json') || str_contains($accept, 'json');

        if ($isApi) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Non autorisé. Veuillez vous connecter.']);
            exit;
        }

        // Redirection vers la page de login
        header('Location: /mes_dossiers/sds/admin/index.php');
        exit;
    }

    // Régénérer l'ID de session périodiquement (toutes les 30 min)
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

/**
 * Récupérer les infos de l'admin connecté
 */
function get_admin(): array {
    return [
        'id'           => $_SESSION['admin_id'] ?? 0,
        'username'     => $_SESSION['admin_username'] ?? '',
        'display_name' => $_SESSION['admin_display_name'] ?? '',
        'role'         => $_SESSION['admin_role'] ?? '',
    ];
}
