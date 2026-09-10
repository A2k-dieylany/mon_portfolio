<?php
/**
 * Installe le CRM et reprend l'existant.
 *
 *   php tools/migrate_crm.php            → simulation
 *   php tools/migrate_crm.php --apply    → applique
 *
 * Reprend :
 *   - les messages du formulaire de contact, en opportunités ;
 *   - les conversations du chatbot marquées comme prospects, en extrayant le
 *     téléphone et l'e-mail du texte échangé — aujourd'hui ces données dorment
 *     dans chatbot_logs sans que personne les voie.
 *
 * Relançable sans risque : chaque opportunité porte sa source exacte
 * (source_ref, unique) et les tables sont créées en IF NOT EXISTS.
 *
 * Prérequis : .env.local à la racine (DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS).
 * Non déployé — voir .vercelignore.
 */

$apply = in_array('--apply', $argv, true);
$root  = dirname(__DIR__);

if (!is_file($root . '/.env.local')) {
    exit("Fichier .env.local introuvable à la racine du projet.\n");
}

$env = [];
foreach (file($root . '/.env.local', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
        continue;
    }
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim(trim($v), "\"'");
}

$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $env['DB_HOST'],
        $env['DB_PORT'] ?? '3306',
        $env['DB_NAME']
    ),
    $env['DB_USER'],
    $env['DB_PASS'],
    [
        PDO::ATTR_ERRMODE                      => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE           => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ]
);

echo $apply ? "MODE ÉCRITURE\n\n" : "SIMULATION — rien ne sera écrit\n\n";

// ----------------------------------------------------------------- 1. Schéma
$existing = [];
foreach ($pdo->query('SHOW TABLES') as $row) {
    $existing[] = array_values($row)[0];
}
$missing = array_diff(['crm_contacts', 'crm_deals', 'crm_activities'], $existing);

if ($missing) {
    echo 'Tables à créer : ' . implode(', ', $missing) . "\n";
    if ($apply) {
        // Les tables se terminent par « ) ENGINE=InnoDB … ; » : on découpe sur le
        // point-virgule, après avoir retiré les commentaires — l'un d'eux en
        // contient un et couperait l'instruction au mauvais endroit.
        $sql = preg_replace('/--.*$/m', '', file_get_contents($root . '/sql/crm.sql'));
        foreach (explode(';', $sql) as $statement) {
            $statement = trim($statement);
            if ($statement === '' || stripos($statement, 'CREATE TABLE') === false) {
                continue;
            }
            $pdo->exec($statement);
        }
        echo "  → créées.\n";
    } else {
        exit("\nRelancez avec --apply : les tables seront créées puis l'existant repris.\n");
    }
} else {
    echo "Tables CRM déjà présentes.\n";
}

/** Extrait un numéro de mobile sénégalais d'un texte libre. */
function find_phone(string $text): ?string
{
    // 70, 75, 76, 77 ou 78 puis sept chiffres, avec ou sans +221,
    // et avec des séparateurs variables (espace, point, tiret).
    $pattern = '/(?:\+?221[\s.\-]?)?(7[05678])[\s.\-]?(\d{3})[\s.\-]?(\d{2})[\s.\-]?(\d{2})/';
    if (preg_match($pattern, $text, $m)) {
        return '+221' . $m[1] . $m[2] . $m[3] . $m[4];
    }
    return null;
}

/** Extrait une adresse e-mail d'un texte libre. */
function find_email(string $text): ?string
{
    if (preg_match('/[\w.+-]+@[\w-]+\.[\w.-]{2,}/', $text, $m)) {
        return rtrim($m[0], '.');
    }
    return null;
}

/** Retrouve ou crée un contact, sans dupliquer sur l'e-mail ou le téléphone. */
function upsert_contact(PDO $pdo, bool $apply, string $name, ?string $email, ?string $phone): ?int
{
    foreach ([['email', $email], ['phone', $phone]] as [$column, $value]) {
        if (!$value) {
            continue;
        }
        $stmt = $pdo->prepare("SELECT id FROM crm_contacts WHERE $column = ? LIMIT 1");
        $stmt->execute([$value]);
        if ($id = $stmt->fetchColumn()) {
            return (int) $id;
        }
    }
    if (!$apply) {
        return null;
    }
    $stmt = $pdo->prepare('INSERT INTO crm_contacts (full_name, email, phone) VALUES (?, ?, ?)');
    $stmt->execute([$name, $email, $phone]);
    return (int) $pdo->lastInsertId();
}

/** Crée une opportunité si sa source n'a pas déjà été importée. */
function insert_deal(PDO $pdo, bool $apply, array $deal): bool
{
    $stmt = $pdo->prepare('SELECT id FROM crm_deals WHERE source_ref = ? LIMIT 1');
    $stmt->execute([$deal['source_ref']]);
    if ($stmt->fetchColumn()) {
        return false;
    }
    if (!$apply) {
        return true;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO crm_deals (contact_id, title, source, stage, summary, source_ref, created_at)
         VALUES (:contact_id, :title, :source, :stage, :summary, :source_ref, :created_at)'
    );
    $stmt->execute($deal);
    return true;
}

$created = ['formulaire' => 0, 'chatbot' => 0];

// -------------------------------------------------- 2. Reprise du formulaire
foreach ($pdo->query('SELECT * FROM messages ORDER BY id') as $message) {
    $phone     = find_phone($message['message'] ?? '');
    $contactId = upsert_contact($pdo, $apply, $message['name'], $message['email'] ?: null, $phone);

    // Un message déjà traité ne doit pas réapparaître comme « nouveau ».
    $stage = match ($message['status'] ?? 'unread') {
        'replied'  => 'contacte',
        'archived' => 'perdu',
        default    => 'nouveau',
    };

    $inserted = insert_deal($pdo, $apply, [
        'contact_id' => $contactId,
        'title'      => mb_substr($message['subject'] ?: 'Demande via le formulaire', 0, 200),
        'source'     => 'formulaire',
        'stage'      => $stage,
        'summary'    => $message['message'],
        'source_ref' => 'message:' . $message['id'],
        'created_at' => $message['created_at'],
    ]);
    if ($inserted) {
        $created['formulaire']++;
    }
}

// ---------------------------------------------------- 3. Reprise du chatbot
$leads = $pdo->query('SELECT session_id, notified_at FROM chatbot_leads')->fetchAll();

foreach ($leads as $lead) {
    $stmt = $pdo->prepare(
        'SELECT user_message, bot_response FROM chatbot_logs WHERE session_id = ? ORDER BY id'
    );
    $stmt->execute([$lead['session_id']]);
    $turns = $stmt->fetchAll();
    if (!$turns) {
        continue;
    }

    $userText   = '';
    $transcript = '';
    foreach ($turns as $turn) {
        $userText   .= ' ' . $turn['user_message'];
        $transcript .= 'Visiteur : ' . trim($turn['user_message']) . "\n"
                     . 'MAX : ' . trim($turn['bot_response']) . "\n\n";
    }

    $phone = find_phone($userText);
    $email = find_email($userText);

    $contactId = ($phone || $email)
        ? upsert_contact($pdo, $apply, 'Prospect chatbot', $email, $phone)
        : null;

    $inserted = insert_deal($pdo, $apply, [
        'contact_id' => $contactId,
        'title'      => 'Conversation chatbot' . ($phone ? " — $phone" : ''),
        'source'     => 'chatbot',
        'stage'      => 'nouveau',
        'summary'    => trim($transcript),
        'source_ref' => 'chat:' . $lead['session_id'],
        'created_at' => $lead['notified_at'],
    ]);
    if ($inserted) {
        $created['chatbot']++;
        printf(
            "  chatbot %s… : %s\n",
            substr($lead['session_id'], 0, 10),
            $phone ?: ($email ?: 'aucune coordonnée dans la conversation')
        );
    }
}

printf("\nOpportunités reprises du formulaire : %d\n", $created['formulaire']);
printf("Opportunités reprises du chatbot    : %d\n", $created['chatbot']);
echo $apply ? "\nTerminé.\n" : "\nSimulation. Relancez avec --apply pour écrire.\n";
