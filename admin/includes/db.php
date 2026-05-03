<?php
/**
 * SDS Admin — Connexion PDO partagée
 * Réutilise config.php existant pour éviter la duplication
 */

require_once __DIR__ . '/../../config.php';

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
            ]
        );
    }
    return $pdo;
}
