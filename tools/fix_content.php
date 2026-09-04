<?php
/**
 * Corrections de contenu en base (Aiven).
 *
 * Usage :
 *   php tools/fix_content.php            → simulation, n'écrit rien
 *   php tools/fix_content.php --apply    → applique les modifications
 *
 * Les identifiants sont lus dans .env.local à la racine (jamais versionné).
 * Non déployé : voir .vercelignore.
 */

$apply = in_array('--apply', $argv, true);

$root = dirname(__DIR__);
foreach (['.env.local', '.env'] as $candidate) {
    if (is_file($root . '/' . $candidate)) { $envFile = $root . '/' . $candidate; break; }
}
if (!isset($envFile)) { exit("Aucun .env.local trouvé à la racine.\n"); }

$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) { continue; }
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim(trim($v), "\"'");
}

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $env['DB_HOST'], $env['DB_PORT'] ?? '3306', $env['DB_NAME'] ?? 'portfolio_sds');

$opts = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
// Aiven impose TLS ; on ne vérifie pas le CN faute de CA local, la connexion
// reste chiffrée et il s'agit d'une opération d'administration ponctuelle.
if (defined('PDO::MYSQL_ATTR_SSL_CA')) {
    $opts[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
}
$pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'], $opts);

printf("Connecté à %s (%s)\n\n", $env['DB_HOST'], $apply ? "MODE ÉCRITURE" : "SIMULATION");

$changes = [];

/** Enregistre une modification à appliquer, avec son avant/après. */
function plan(array &$changes, string $label, string $sql, array $params, array $before): void {
    $changes[] = compact('label', 'sql', 'params', 'before');
}

// ---------------------------------------------------------------- témoignages
// Aucun des deux n'a été écrit par la personne citée : on les dépublie plutôt
// que de les supprimer, pour pouvoir les rétablir s'ils sont un jour obtenus.
$rows = $pdo->query("SELECT id, client_name, role_fr FROM testimonials WHERE is_visible = 1")->fetchAll();
foreach ($rows as $r) {
    plan($changes, "Dépublier le témoignage de « {$r['client_name']} »",
        "UPDATE testimonials SET is_visible = 0 WHERE id = ?", [$r['id']],
        [$r['client_name'] . ' — ' . $r['role_fr'] => 'visible']);
}

// ------------------------------------------------------------- liens GitHub
$repoFixes = [
    'luxegold'          => 'https://github.com/A2k-dieylany/LuxeGold-Ecommerce',
    'sds-whatsapp-bot'  => null,  // le dépôt n'existe pas encore
    'sen-event'         => null,  // projet de design, un dépôt n'apporte rien
];
$rows = $pdo->query("SELECT id, title_fr, github_url FROM projects WHERE github_url IS NOT NULL AND github_url <> ''")->fetchAll();
foreach ($rows as $r) {
    $url = $r['github_url'];
    $new = $url;
    foreach ($repoFixes as $needle => $replacement) {
        if (stripos($url, '/' . $needle) !== false) {
            $new = $replacement;
            break;
        }
    }
    // Normalise la casse du compte, pour rester cohérent partout.
    if ($new !== null) {
        $new = str_replace('github.com/a2k-dieylany', 'github.com/A2k-dieylany', $new);
    }
    if ($new !== $url) {
        plan($changes,
            $new === null
                ? "Retirer le lien GitHub mort de « {$r['title_fr']} »"
                : "Corriger le lien GitHub de « {$r['title_fr']} »",
            "UPDATE projects SET github_url = ? WHERE id = ?", [$new, $r['id']],
            [$url => $new ?? '(aucun)']);
    }
}

// -------------------------------------------------------------------- rendu
if (!$changes) {
    exit("Rien à modifier.\n");
}
foreach ($changes as $i => $c) {
    printf("%2d. %s\n", $i + 1, $c['label']);
    foreach ($c['before'] as $from => $to) {
        printf("      %s\n        -> %s\n", $from, $to);
    }
}
printf("\n%d modification(s).\n", count($changes));

if (!$apply) {
    exit("\nSimulation uniquement. Relancer avec --apply pour écrire.\n");
}

$pdo->beginTransaction();
try {
    foreach ($changes as $c) {
        $pdo->prepare($c['sql'])->execute($c['params']);
    }
    $pdo->commit();
    echo "\nAppliqué.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    exit("\nÉchec, rien n'a été modifié : " . $e->getMessage() . "\n");
}
