<?php
/**
 * SDS Admin — Connexion PDO partagée
 * Réutilise config.php existant pour éviter la duplication
 */

require_once __DIR__ . '/../../config.php';

// ===== Configuration sécurisée des sessions =====
// Appliquer ces paramètres avant tout session_start()
if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,
        'httponly'  => true,
        'samesite'  => 'Lax',
    ]);
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $host   = defined('DB_HOST') ? DB_HOST : 'localhost';
        $dbname = defined('DB_NAME') ? DB_NAME : 'portfolio_sds';
        $user   = defined('DB_USER') ? DB_USER : 'root';
        $pass   = defined('DB_PASS') ? DB_PASS : '';

        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        );
    }
    return $pdo;
}
