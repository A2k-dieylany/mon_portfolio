<?php
// Le runtime Vercel tourne en PHP 8.5 ; on masque les E_DEPRECATED (constantes
// PDO renommées, etc.) pour qu'ils ne polluent pas la sortie avant les headers.
error_reporting(E_ALL & ~E_DEPRECATED);

// L'edge Vercel compresse les fichiers statiques mais pas la sortie de la
// fonction PHP : l'accueil partait en ~110 Ko de HTML brut. zlib négocie
// lui-même avec l'en-tête Accept-Encoding du client.
if (!headers_sent() && !ini_get('zlib.output_compression')) {
    @ini_set('zlib.output_compression', '1');
    @ini_set('zlib.output_compression_level', '5');
}

/**
 * SDS — Point d'entrée unique pour Vercel.
 *
 * Le runtime PHP communautaire ne crée qu'une seule fonction serverless par
 * entrée "builds" (le plan Hobby de Vercel plafonne à 12 fonctions), donc
 * toutes les requêtes PHP passent par ce dispatcher qui charge le bon fichier
 * en interne via require — le routage HTTP direct par fichier ne fonctionne
 * pas correctement avec ce runtime sur ce projet.
 */

/** Rend la page 404 du site plutôt qu'un « Not Found » en texte brut. */
function sds_not_found(): void {
    $page = __DIR__ . '/404.php';
    if (is_file($page)) {
        require $page;
    } else {
        http_response_code(404);
        echo 'Not Found';
    }
    exit;
}

/**
 * Rend la page de maintenance au lieu d'une trace PHP.
 *
 * Une base injoignable affichait jusqu'ici une PDOException complète, avec les
 * chemins serveur et la ligne fautive, et un code HTTP 200 — Google pouvait
 * donc indexer la page d'erreur. On répond 503 avec un message présentable.
 */
function sds_service_unavailable(Throwable $e): void {
    error_log('SDS indisponible : ' . $e->getMessage());

    if (!headers_sent()) {
        // Les points d'entrée JSON doivent recevoir du JSON, pas du HTML.
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '';
        $wantsJson = str_contains($path, '/api/')
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || in_array(basename($path), ['chat.php', 'contact.php', 'visitors.php'], true);

        if ($wantsJson) {
            http_response_code(503);
            header('Retry-After: 300');
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['error' => 'Service temporairement indisponible'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $page = __DIR__ . '/maintenance.php';
    if (is_file($page)) {
        require $page;
    } else {
        http_response_code(503);
        echo 'Service temporairement indisponible';
    }
    exit;
}

// En production, une erreur non rattrapée ne doit jamais s'afficher au visiteur.
@ini_set('display_errors', '0');
set_exception_handler('sds_service_unavailable');

// Domaine canonique : on redirige www -> apex pour éviter le contenu dupliqué.
$host = strtolower($_SERVER['HTTP_HOST'] ?? '');
if (str_starts_with($host, 'www.')) {
    $canonicalHost = substr($host, 4);
    header('Location: https://' . $canonicalHost . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = urldecode($uri ?? '/');

if ($uri === '/' || $uri === '') {
    $target = '/index.php';
} elseif ($uri === '/admin' || $uri === '/admin/') {
    $target = '/admin/index.php';
} elseif (preg_match('#^/services/([a-z0-9\-]+)/?$#', $uri, $m)) {
    // URL propre des pages service : /services/<slug>
    $_GET['slug'] = $m[1];
    $target = '/service.php';
} elseif ($uri === '/sitemap.xml') {
    // Sitemap généré depuis la base (les pages services s'y ajoutent seules)
    $target = '/sitemap.php';
} elseif (str_ends_with($uri, '.php')) {
    $target = $uri;
} else {
    sds_not_found();
}

$relative = ltrim($target, '/');
$realBase = realpath(__DIR__);
$realTarget = realpath(__DIR__ . '/' . $relative);

// Fichiers d'inclusion interne : jamais accessibles directement en HTTP.
$blockedBasenames = ['config.php', 'session_bootstrap.php', '_app.php', '404.php', 'maintenance.php'];

$isBlocked = in_array(basename($relative), $blockedBasenames, true)
    || str_contains($relative, 'includes/')
    || str_contains($relative, '_archives/');

if (
    $isBlocked
    || $realTarget === false
    || $realBase === false
    || strpos($realTarget, $realBase . DIRECTORY_SEPARATOR) !== 0
    || !is_file($realTarget)
) {
    sds_not_found();
}

require $realTarget;
