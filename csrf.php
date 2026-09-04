<?php
require_once __DIR__ . '/session_bootstrap.php';
sds_session_start();
header('Content-Type: application/json');

// Générer un token CSRF unique par session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

echo json_encode(['token' => $_SESSION['csrf_token']]);
?>