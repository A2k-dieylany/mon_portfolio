<?php
/**
 * Capture des prospects dans le CRM depuis les points d'entrée publics.
 *
 * Placé sous includes/ : le dispatcher refuse d'y servir un fichier en HTTP.
 *
 * Toutes les fonctions sont conçues pour ne jamais interrompre l'appelant :
 * si le CRM n'est pas encore installé ou si l'écriture échoue, le formulaire
 * de contact et le chatbot doivent continuer à fonctionner normalement.
 */

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit;
}

/** Extrait un numéro de mobile sénégalais d'un texte libre. */
function sds_crm_find_phone(string $text): ?string
{
    // 70, 75, 76, 77 ou 78 puis sept chiffres, avec ou sans +221,
    // et des séparateurs variables (espace, point, tiret).
    $pattern = '/(?:\+?221[\s.\-]?)?(7[05678])[\s.\-]?(\d{3})[\s.\-]?(\d{2})[\s.\-]?(\d{2})/';
    if (preg_match($pattern, $text, $m)) {
        return '+221' . $m[1] . $m[2] . $m[3] . $m[4];
    }
    return null;
}

/** Extrait une adresse e-mail d'un texte libre. */
function sds_crm_find_email(string $text): ?string
{
    if (preg_match('/[\w.+-]+@[\w-]+\.[\w.-]{2,}/', $text, $m)) {
        return rtrim($m[0], '.');
    }
    return null;
}

/**
 * Enregistre une opportunité, en rattachant le contact s'il existe déjà.
 *
 * `source_ref` rend l'opération idempotente : une même demande rejouée
 * (double envoi de formulaire, relance du chatbot) ne crée pas de doublon.
 *
 * @return int|null L'identifiant de l'opportunité, ou null si rien n'a été fait.
 */
function sds_crm_capture(PDO $pdo, array $o): ?int
{
    try {
        // Le CRM peut ne pas encore être installé : on sort sans bruit.
        if (!$pdo->query("SHOW TABLES LIKE 'crm_deals'")->fetchColumn()) {
            return null;
        }

        $ref = (string) ($o['source_ref'] ?? '');
        if ($ref !== '') {
            $stmt = $pdo->prepare('SELECT id FROM crm_deals WHERE source_ref = ? LIMIT 1');
            $stmt->execute([$ref]);
            if ($existing = $stmt->fetchColumn()) {
                return (int) $existing;
            }
        }

        $email = $o['email'] ?: null;
        $phone = $o['phone'] ?: null;

        $contactId = null;
        foreach ([['email', $email], ['phone', $phone]] as [$column, $value]) {
            if (!$value) {
                continue;
            }
            $stmt = $pdo->prepare("SELECT id FROM crm_contacts WHERE $column = ? LIMIT 1");
            $stmt->execute([$value]);
            if ($found = $stmt->fetchColumn()) {
                $contactId = (int) $found;
                break;
            }
        }

        if (!$contactId && ($email || $phone || !empty($o['name']))) {
            $stmt = $pdo->prepare(
                'INSERT INTO crm_contacts (full_name, email, phone) VALUES (?, ?, ?)'
            );
            $stmt->execute([$o['name'] ?: 'Sans nom', $email, $phone]);
            $contactId = (int) $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare(
            'INSERT INTO crm_deals (contact_id, title, source, stage, summary, source_ref)
             VALUES (?, ?, ?, \'nouveau\', ?, ?)'
        );
        $stmt->execute([
            $contactId,
            mb_substr((string) $o['title'], 0, 200),
            $o['source'],
            $o['summary'] ?? null,
            $ref ?: null,
        ]);

        return (int) $pdo->lastInsertId();
    } catch (Throwable $e) {
        // Un CRM en panne ne doit jamais faire échouer un formulaire public.
        error_log('CRM capture : ' . $e->getMessage());
        return null;
    }
}
