<?php
/**
 * SDS Admin API — CRM (contacts, opportunités, historique)
 *
 * GET                      → pipeline complet + indicateurs
 * GET  ?id=12              → une opportunité, son contact et son historique
 * POST { action: ... }     → create_deal | add_activity
 * PUT  { id, ... }         → met à jour l'opportunité et/ou son contact
 * DELETE ?id=12            → supprime l'opportunité (l'historique suit)
 */
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

require_auth();
security_headers();

const CRM_STAGES  = ['nouveau', 'contacte', 'devis', 'gagne', 'perdu'];
const CRM_SOURCES = ['chatbot', 'formulaire', 'whatsapp', 'recommandation', 'direct', 'autre'];
const CRM_KINDS   = ['note', 'appel', 'whatsapp', 'email', 'rdv', 'systeme'];

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$admin  = get_admin();

// Tant que la migration n'a pas tourné, on le dit explicitement plutôt que de
// laisser remonter une erreur SQL incompréhensible.
if (!$pdo->query("SHOW TABLES LIKE 'crm_deals'")->fetchColumn()) {
    json_response([
        'error' => "Le CRM n'est pas encore installé. Lancez : php tools/migrate_crm.php --apply",
        'setup_required' => true,
    ], 503);
}

/** Journalise un évènement sur l'opportunité, pour garder la trace de tout. */
function crm_log(PDO $pdo, int $dealId, string $body, string $author, string $kind = 'systeme'): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO crm_activities (deal_id, kind, body, author) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$dealId, $kind, $body, $author]);
}

/**
 * Enregistre le contact d'une opportunité.
 *
 * Un même prospect revient souvent par plusieurs canaux : on rattache sur
 * l'e-mail ou le téléphone plutôt que de créer une fiche à chaque fois.
 */
function crm_upsert_contact(PDO $pdo, array $d, ?int $knownId = null): ?int
{
    $name  = trim((string) ($d['contact_name'] ?? ''));
    $email = trim((string) ($d['contact_email'] ?? '')) ?: null;
    $phone = trim((string) ($d['contact_phone'] ?? '')) ?: null;
    $company = trim((string) ($d['contact_company'] ?? '')) ?: null;

    if ($name === '' && !$email && !$phone) {
        return $knownId;
    }

    if ($knownId) {
        $stmt = $pdo->prepare(
            'UPDATE crm_contacts SET full_name = COALESCE(NULLIF(?, \'\'), full_name),
                    company = ?, email = ?, phone = ? WHERE id = ?'
        );
        $stmt->execute([$name, $company, $email, $phone, $knownId]);
        return $knownId;
    }

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

    $stmt = $pdo->prepare(
        'INSERT INTO crm_contacts (full_name, company, email, phone) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$name ?: 'Sans nom', $company, $email, $phone]);
    return (int) $pdo->lastInsertId();
}

try {
    // ============================================================ LECTURE
    if ($method === 'GET') {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($id) {
            $stmt = $pdo->prepare(
                'SELECT d.*, c.full_name AS contact_name, c.company AS contact_company,
                        c.email AS contact_email, c.phone AS contact_phone, c.kind AS contact_kind
                   FROM crm_deals d
                   LEFT JOIN crm_contacts c ON c.id = d.contact_id
                  WHERE d.id = ? LIMIT 1'
            );
            $stmt->execute([$id]);
            $deal = $stmt->fetch();
            if (!$deal) {
                json_response(['error' => 'Opportunité introuvable.'], 404);
            }

            $stmt = $pdo->prepare(
                'SELECT * FROM crm_activities WHERE deal_id = ? ORDER BY created_at DESC, id DESC'
            );
            $stmt->execute([$id]);

            json_response(['deal' => $deal, 'activities' => $stmt->fetchAll()]);
        }

        $deals = $pdo->query(
            'SELECT d.id, d.title, d.source, d.stage, d.amount, d.next_action, d.next_action_at,
                    d.summary, d.created_at, d.contact_id,
                    c.full_name AS contact_name, c.company AS contact_company,
                    c.email AS contact_email, c.phone AS contact_phone
               FROM crm_deals d
               LEFT JOIN crm_contacts c ON c.id = d.contact_id
              ORDER BY d.updated_at DESC, d.id DESC'
        )->fetchAll();

        // Indicateurs : ce qui est en jeu, ce qui est signé, ce qui est en retard.
        $stats = [
            'open'        => 0,
            'open_amount' => 0.0,
            'won'         => 0,
            'won_amount'  => 0.0,
            'overdue'     => 0,
        ];
        $today = date('Y-m-d');
        foreach ($deals as $d) {
            if ($d['stage'] === 'gagne') {
                $stats['won']++;
                $stats['won_amount'] += (float) $d['amount'];
            } elseif ($d['stage'] !== 'perdu') {
                $stats['open']++;
                $stats['open_amount'] += (float) $d['amount'];
                if ($d['next_action_at'] && $d['next_action_at'] < $today) {
                    $stats['overdue']++;
                }
            }
        }

        json_response(['deals' => $deals, 'stats' => $stats]);
    }

    // ============================================================ CRÉATION
    if ($method === 'POST') {
        $data   = get_json_body();
        $action = $data['action'] ?? '';

        if ($action === 'add_activity') {
            require_fields($data, ['deal_id', 'body']);
            $kind = in_array($data['kind'] ?? 'note', CRM_KINDS, true) ? $data['kind'] : 'note';
            $body = trim((string) $data['body']);
            if ($body === '') {
                json_response(['error' => 'Le contenu est vide.'], 422);
            }
            crm_log($pdo, (int) $data['deal_id'], $body, $admin['display_name'], $kind);
            json_response(['success' => true]);
        }

        if ($action !== 'create_deal') {
            json_response(['error' => 'Action non reconnue.'], 400);
        }

        require_fields($data, ['title']);
        $contactId = crm_upsert_contact($pdo, $data);

        $stmt = $pdo->prepare(
            'INSERT INTO crm_deals (contact_id, title, source, stage, amount, summary,
                                    next_action, next_action_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $contactId,
            mb_substr(trim((string) $data['title']), 0, 200),
            in_array($data['source'] ?? '', CRM_SOURCES, true) ? $data['source'] : 'direct',
            in_array($data['stage'] ?? '', CRM_STAGES, true) ? $data['stage'] : 'nouveau',
            $data['amount'] !== '' && isset($data['amount']) ? (float) $data['amount'] : null,
            $data['summary'] ?? null,
            $data['next_action'] ?? null,
            $data['next_action_at'] ?: null,
        ]);

        $dealId = (int) $pdo->lastInsertId();
        crm_log($pdo, $dealId, 'Opportunité créée.', $admin['display_name']);
        json_response(['success' => true, 'id' => $dealId], 201);
    }

    // ============================================================== MISE À JOUR
    if ($method === 'PUT') {
        $data = get_json_body();
        require_fields($data, ['id']);
        $id = (int) $data['id'];

        $stmt = $pdo->prepare('SELECT * FROM crm_deals WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $before = $stmt->fetch();
        if (!$before) {
            json_response(['error' => 'Opportunité introuvable.'], 404);
        }

        // Un seul appel : appelé deux fois, il créait une seconde fiche quand
        // seul un nom était saisi (rien à rapprocher sur l'e-mail ou le téléphone).
        $contactId = crm_upsert_contact(
            $pdo,
            $data,
            $before['contact_id'] ? (int) $before['contact_id'] : null
        );
        if ($contactId && (int) $before['contact_id'] !== $contactId) {
            $pdo->prepare('UPDATE crm_deals SET contact_id = ? WHERE id = ?')
                ->execute([$contactId, $id]);
        }

        $sets = [];
        $args = [];
        $map  = [
            'title'          => fn($v) => mb_substr(trim((string) $v), 0, 200),
            'summary'        => fn($v) => $v,
            'next_action'    => fn($v) => $v ?: null,
            'next_action_at' => fn($v) => $v ?: null,
            'lost_reason'    => fn($v) => $v ?: null,
            'amount'         => fn($v) => ($v === '' || $v === null) ? null : (float) $v,
        ];
        foreach ($map as $field => $cast) {
            if (array_key_exists($field, $data)) {
                $sets[] = "$field = ?";
                $args[] = $cast($data[$field]);
            }
        }
        if (isset($data['stage']) && in_array($data['stage'], CRM_STAGES, true)) {
            $sets[] = 'stage = ?';
            $args[] = $data['stage'];
            // Une affaire close est datée : c'est ce qui permettra de mesurer
            // le délai moyen entre la demande et la signature.
            $sets[] = 'closed_at = ?';
            $args[] = in_array($data['stage'], ['gagne', 'perdu'], true) ? date('Y-m-d H:i:s') : null;
        }
        if (isset($data['source']) && in_array($data['source'], CRM_SOURCES, true)) {
            $sets[] = 'source = ?';
            $args[] = $data['source'];
        }

        if ($sets) {
            $args[] = $id;
            $pdo->prepare('UPDATE crm_deals SET ' . implode(', ', $sets) . ' WHERE id = ?')
                ->execute($args);
        }

        if (isset($data['stage']) && $data['stage'] !== $before['stage']) {
            crm_log(
                $pdo,
                $id,
                sprintf('Étape : %s → %s', $before['stage'], $data['stage']),
                $admin['display_name']
            );
            // Un prospect qui signe devient un client.
            if ($data['stage'] === 'gagne' && $contactId) {
                $pdo->prepare("UPDATE crm_contacts SET kind = 'client' WHERE id = ?")
                    ->execute([$contactId]);
            }
        }

        json_response(['success' => true]);
    }

    // ============================================================ SUPPRESSION
    if ($method === 'DELETE') {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            json_response(['error' => 'Identifiant manquant.'], 400);
        }
        $pdo->prepare('DELETE FROM crm_deals WHERE id = ?')->execute([$id]);
        json_response(['success' => true]);
    }

    json_response(['error' => 'Méthode non autorisée.'], 405);
} catch (Throwable $e) {
    error_log('CRM : ' . $e->getMessage());
    json_response(['error' => 'Erreur serveur.'], 500);
}
