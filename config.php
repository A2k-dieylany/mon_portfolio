<?php
// config.php — ne contient aucun secret : les valeurs viennent de .env (local)
// ou des variables d'environnement du projet Vercel (production).

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit;
}

function loadEnv($path) {
    if (!file_exists($path)) {
        return; // ou throw exception si critique
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Charger le fichier .env à la racine
loadEnv(__DIR__ . '/.env');

/**
 * URL d'un asset statique, suffixée d'une empreinte de version.
 *
 * Les fichiers ne sont pas nommés avec un hash, donc sans ce paramètre on ne
 * peut pas les mettre en cache longue durée sans risquer de servir du CSS
 * périmé après un déploiement. Le mtime change à chaque déploiement Vercel.
 */
function sds_asset(string $path): string {
    $clean = '/' . ltrim($path, '/');
    $file  = __DIR__ . $clean;
    $v     = @filemtime($file) ?: 1;
    return $clean . '?v=' . $v;
}

// ===== Constantes Globales (Rétrocompatibilité avec le code existant) =====
define('GROQ_API_KEY', $_ENV['GROQ_API_KEY'] ?? '');
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'portfolio_sds');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
// Contenu PEM du certificat CA (requis par Aiven/TiDB/etc. en production).
// Vide en local (XAMPP ne chiffre pas la connexion MySQL par défaut).
define('DB_SSL_CA', $_ENV['DB_SSL_CA'] ?? '');
define('BLOB_READ_WRITE_TOKEN', $_ENV['BLOB_READ_WRITE_TOKEN'] ?? '');
define('RESEND_API_KEY', $_ENV['RESEND_API_KEY'] ?? '');
define('WEBHOOK_URL', $_ENV['WEBHOOK_URL'] ?? '');
define('META_API_TOKEN', $_ENV['META_API_TOKEN'] ?? '');
define('META_PHONE_ID', $_ENV['META_PHONE_ID'] ?? '');
define('META_TARGET_PHONE', $_ENV['META_TARGET_PHONE'] ?? '');