<?php
/**
 * Sitemap généré dynamiquement : les pages services s'y ajoutent toutes seules
 * dès qu'un service reçoit un slug et un contenu détaillé.
 * Servi sur /sitemap.xml via le routage de _app.php.
 */

require_once __DIR__ . '/admin/includes/db.php';

header('Content-Type: application/xml; charset=UTF-8');

$base = 'https://dieylany.dev';
$urls = [
    ['loc' => $base . '/', 'priority' => '1.0', 'changefreq' => 'weekly'],
];

try {
    $pdo = getDB();
    $stmt = $pdo->query(
        "SELECT slug FROM services
         WHERE is_visible = 1 AND slug IS NOT NULL AND slug <> '' AND detail_fr IS NOT NULL
         ORDER BY sort_order ASC"
    );
    foreach ($stmt as $row) {
        $urls[] = [
            'loc'        => $base . '/services/' . $row['slug'],
            'priority'   => '0.8',
            'changefreq' => 'monthly',
        ];
    }
} catch (Throwable $e) {
    // Base indisponible : on sert au moins la page d'accueil plutôt qu'une erreur.
    error_log('Sitemap: ' . $e->getMessage());
}

$today = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
    echo "    <lastmod>$today</lastmod>\n";
    echo '    <changefreq>' . $u['changefreq'] . "</changefreq>\n";
    echo '    <priority>' . $u['priority'] . "</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>' . "\n";
