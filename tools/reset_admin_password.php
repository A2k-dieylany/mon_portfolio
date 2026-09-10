<?php
/**
 * Réinitialise le mot de passe d'un compte administrateur.
 *
 * À lancer en local, jamais déployé (voir .vercelignore) :
 *     php tools/reset_admin_password.php
 *
 * Le mot de passe est saisi au clavier : il n'apparaît ni dans la ligne de
 * commande, ni dans l'historique du terminal. Le script remet aussi à zéro le
 * compteur de tentatives et lève le verrouillage anti-force-brute.
 *
 * Prérequis : un fichier .env.local à la racine du projet contenant
 *     DB_HOST=...        (l'adresse du serveur Aiven)
 *     DB_PORT=...
 *     DB_NAME=portfolio_sds
 *     DB_USER=avnadmin
 *     DB_PASS=...
 * Ce fichier est ignoré par git et par Vercel. Supprimez-le après usage.
 */

$root    = dirname(__DIR__);
$envFile = $root . '/.env.local';

if (!is_file($envFile)) {
    exit("Fichier introuvable : $envFile\nCréez-le avec DB_HOST, DB_PORT, DB_NAME, DB_USER et DB_PASS.\n");
}

$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
        continue;
    }
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim(trim($v), "\"'");
}

foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $required) {
    if (empty($env[$required])) {
        exit("Variable manquante dans .env.local : $required\n");
    }
}

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $env['DB_HOST'], $env['DB_PORT'] ?? '3306', $env['DB_NAME']),
        $env['DB_USER'],
        $env['DB_PASS'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Aiven impose TLS ; le certificat CA n'est pas présent en local,
            // la connexion reste chiffrée mais le nom du serveur n'est pas validé.
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ]
    );
} catch (Throwable $e) {
    echo "Connexion impossible : " . $e->getMessage() . "\n";
    exit("Si le message parle d'un délai dépassé, mettez l'adresse IP du serveur\n"
       . "dans DB_HOST plutôt que son nom : la résolution DNS échoue parfois ici.\n");
}

$users = $pdo->query("SELECT id, username, display_name, role, locked_until FROM admin_users ORDER BY id")->fetchAll();
if (!$users) {
    exit("Aucun compte dans admin_users.\n");
}

echo "Comptes administrateurs :\n";
foreach ($users as $u) {
    printf("  [%d] %-16s %-22s %-10s%s\n",
        $u['id'], $u['username'], $u['display_name'], $u['role'],
        $u['locked_until'] ? '  (verrouillé jusqu\'à ' . $u['locked_until'] . ')' : '');
}

if (count($users) === 1) {
    $target = $users[0];
    echo "\nCompte sélectionné : {$target['username']}\n";
} else {
    echo "\nIdentifiant du compte à réinitialiser : ";
    $id = trim(fgets(STDIN));
    $found = array_filter($users, static fn($u) => (string) $u['id'] === $id);
    if (!$found) {
        exit("Identifiant inconnu.\n");
    }
    $target = array_values($found)[0];
}

/** Lit une saisie sans l'afficher à l'écran quand c'est possible. */
function prompt_secret(string $label): string {
    echo $label;
    if (stripos(PHP_OS_FAMILY, 'Windows') === false && is_readable('/dev/tty')) {
        shell_exec('stty -echo 2>/dev/null');
        $value = trim((string) fgets(STDIN));
        shell_exec('stty echo 2>/dev/null');
        echo "\n";
        return $value;
    }
    // Sous Windows la frappe reste visible : pensez à effacer l'écran ensuite.
    return trim((string) fgets(STDIN));
}

$new     = prompt_secret("Nouveau mot de passe (10 caractères minimum) : ");
$confirm = prompt_secret("Confirmez le mot de passe                    : ");

if ($new !== $confirm) {
    exit("Les deux saisies diffèrent, rien n'a été modifié.\n");
}
if (mb_strlen($new) < 10) {
    exit("Mot de passe trop court, rien n'a été modifié.\n");
}

$stmt = $pdo->prepare(
    "UPDATE admin_users
        SET password_hash = :hash, login_attempts = 0, locked_until = NULL
      WHERE id = :id"
);
$stmt->execute([
    ':hash' => password_hash($new, PASSWORD_DEFAULT),
    ':id'   => $target['id'],
]);

echo "\nMot de passe réinitialisé pour « {$target['username']} ».\n";
echo "Verrouillage levé et compteur de tentatives remis à zéro.\n";
echo "Connectez-vous sur https://dieylany.dev/admin puis supprimez .env.local.\n";
