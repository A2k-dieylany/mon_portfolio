<?php
/**
 * SDS Admin — Déconnexion (formulaire POST du tableau de bord)
 */
require_once __DIR__ . '/../session_bootstrap.php';

// Déconnexion en POST uniquement : en GET, un simple lien placé sur
// n'importe quel site suffisait à fermer la session de l'administrateur.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

sds_session_start();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();
header('Location: index.php');
exit;
