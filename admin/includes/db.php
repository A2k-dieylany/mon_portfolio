<?php
/**
 * SDS Admin — Connexion PDO partagée
 * Réutilise config.php existant pour éviter la duplication
 */

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../../config.php';

/**
 * PHP 8.5 a déprécié les constantes PDO::MYSQL_ATTR_* au profit de
 * Pdo\Mysql::ATTR_* (classe absente avant 8.4). On résout dynamiquement pour
 * rester compatible avec le PHP local (8.0, XAMPP) et celui de Vercel (8.5).
 */
function sds_pdo_mysql_const(string $name) {
    if (class_exists('Pdo\\Mysql') && defined("Pdo\\Mysql::$name")) {
        return constant("Pdo\\Mysql::$name");
    }
    return constant("PDO::MYSQL_$name");
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $host   = defined('DB_HOST') ? DB_HOST : 'localhost';
        $port   = defined('DB_PORT') ? DB_PORT : '3306';
        $dbname = defined('DB_NAME') ? DB_NAME : 'portfolio_sds';
        $user   = defined('DB_USER') ? DB_USER : 'root';
        $pass   = defined('DB_PASS') ? DB_PASS : '';

        $options = [
            PDO::ATTR_ERRMODE                 => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE      => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES        => false,
            sds_pdo_mysql_const('ATTR_INIT_COMMAND') => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        // Connexion chiffrée (requise par la plupart des hébergeurs MySQL
        // externes type Aiven/TiDB/PlanetScale). Le contenu PEM du certificat
        // CA est fourni via une variable d'env (jamais commité dans le repo)
        // et écrit dans /tmp au premier appel, seul répertoire inscriptible
        // sur Vercel.
        $sslCaContent = defined('DB_SSL_CA') ? DB_SSL_CA : '';
        if ($sslCaContent !== '') {
            $caPath = '/tmp/sds-db-ca.pem';
            if (!file_exists($caPath)) {
                file_put_contents($caPath, $sslCaContent);
            }
            $options[sds_pdo_mysql_const('ATTR_SSL_CA')] = $caPath;
            $options[sds_pdo_mysql_const('ATTR_SSL_VERIFY_SERVER_CERT')] = true;
        }

        $pdo = new PDO(
            "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
            $user,
            $pass,
            $options
        );
    }
    return $pdo;
}
