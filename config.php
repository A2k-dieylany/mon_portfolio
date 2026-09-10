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
 * Empreinte de version des assets, calculée une seule fois par requête.
 *
 * Vercel normalise les dates de modification des fichiers au build : filemtime
 * renvoie la même valeur fixe à chaque déploiement et ne peut donc pas servir
 * de cache-buster. On se base sur l'identifiant du déploiement, unique par build.
 */
function sds_asset_version(): string {
    static $v = null;
    if ($v !== null) {
        return $v;
    }
    foreach (['VERCEL_DEPLOYMENT_ID', 'VERCEL_GIT_COMMIT_SHA', 'VERCEL_URL'] as $key) {
        $candidate = getenv($key) ?: ($_SERVER[$key] ?? '');
        if ($candidate !== '') {
            return $v = substr(hash('crc32b', $candidate), 0, 8);
        }
    }
    // Hors Vercel (XAMPP en local) : le mtime reflète bien les modifications.
    return $v = (string) (@filemtime(__DIR__ . '/style.css') ?: time());
}

/**
 * URL d'un asset statique, suffixée de l'empreinte du déploiement.
 *
 * Les fichiers ne sont pas nommés avec un hash ; sans ce paramètre on ne peut
 * pas les servir en cache immutable sans risquer du CSS périmé après un
 * déploiement.
 */
function sds_asset(string $path): string {
    return '/' . ltrim($path, '/') . '?v=' . sds_asset_version();
}

/**
 * Adresse IP réelle du visiteur.
 *
 * Sur Vercel, PHP tourne derrière un proxy local : REMOTE_ADDR vaut 127.0.0.1
 * pour tout le monde. Toutes les visites avaient donc la même empreinte — le
 * compteur n'enregistrait qu'un visiteur par jour — et le quota du chatbot
 * était partagé par tous les visiteurs réunis.
 *
 * Les en-têtes transmis par le proxy ne sont lus que si la connexion vient
 * elle-même d'une adresse locale ou privée : exposé directement, un client
 * pourrait les forger. Sur Vercel, l'edge les écrase avec l'adresse vérifiée.
 */
function sds_client_ip(): string
{
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    $public = filter_var($remote, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);

    if ($public === false) {
        foreach (['HTTP_X_REAL_IP', 'HTTP_X_VERCEL_FORWARDED_FOR', 'HTTP_X_FORWARDED_FOR'] as $header) {
            // X-Forwarded-For peut lister plusieurs relais : le client est le premier.
            $candidate = trim(explode(',', $_SERVER[$header] ?? '')[0]);
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }
    }
    return $remote !== '' ? $remote : '0.0.0.0';
}

/** Pays du visiteur (code ISO à deux lettres) fourni par l'edge Vercel ; vide ailleurs. */
function sds_client_country(): string
{
    $code = strtoupper(trim($_SERVER['HTTP_X_VERCEL_IP_COUNTRY'] ?? ''));
    return preg_match('/^[A-Z]{2}$/', $code) ? $code : '';
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
// Jeton envoyé par Vercel dans l'en-tête Authorization des tâches planifiées.
// Sans lui, le point d'entrée des relances refuse de s'exécuter.
define('CRON_SECRET', $_ENV['CRON_SECRET'] ?? '');

// Destinataire des alertes et du récapitulatif quotidien.
// Tant que le domaine n'est pas vérifié chez Resend, l'expéditeur de test
// n'écrit qu'à l'adresse du compte Resend : toute autre valeur est rejetée.
define('ALERT_EMAIL', $_ENV['ALERT_EMAIL'] ?? 'dieylany.dev@gmail.com');
define('ALERT_FROM', $_ENV['ALERT_FROM'] ?? 'MAX <onboarding@resend.dev>');