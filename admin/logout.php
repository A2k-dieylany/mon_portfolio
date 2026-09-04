<?php
/**
 * SDS Admin — Déconnexion directe (lien GET)
 */
require_once __DIR__ . '/../session_bootstrap.php';
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
