<?php
/**
 * Passage quotidien sur le pipeline : ce qui dort, ce qui traîne, ce qui a
 * été ouvert. Envoie un seul e-mail récapitulatif plutôt qu'une alerte par
 * opportunité — une boîte saturée finit ignorée.
 *
 * Déclenché par la tâche planifiée Vercel (voir "crons" dans vercel.json).
 * Vercel envoie l'en-tête Authorization: Bearer <CRON_SECRET>.
 */

require_once __DIR__ . '/admin/includes/db.php';

header('Content-Type: application/json; charset=UTF-8');

// Fermé par défaut : sans jeton configuré, personne ne déclenche rien.
$expected = defined('CRON_SECRET') ? CRON_SECRET : '';
$provided = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (str_starts_with($provided, 'Bearer ')) {
    $provided = substr($provided, 7);
}

if ($expected === '' || !hash_equals($expected, $provided)) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé.']);
    exit;
}

/** Formate un montant en francs CFA. */
function relance_money($amount): string
{
    return number_format((float) $amount, 0, ',', ' ') . ' F';
}

/** Décrit un contact en une ligne lisible. */
function relance_who(array $row): string
{
    $who = trim(($row['full_name'] ?? '') . ' · ' . ($row['company'] ?? ''), ' ·') ?: 'Contact inconnu';
    if (!empty($row['phone'])) {
        $who .= ' — ' . $row['phone'];
    }
    return $who;
}

try {
    $pdo = getDB();
    $today = date('Y-m-d');

    // 1. Relances dont la date est passée : la promesse faite au prospect.
    $overdue = $pdo->query(
        "SELECT d.id, d.title, d.amount, d.next_action, d.next_action_at,
                c.full_name, c.company, c.phone
           FROM crm_deals d
           LEFT JOIN crm_contacts c ON c.id = d.contact_id
          WHERE d.stage NOT IN ('gagne','perdu')
            AND d.followup_paused = 0
            AND d.next_action_at IS NOT NULL
            AND d.next_action_at < CURDATE()
          ORDER BY d.next_action_at ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    // 2. Opportunités ouvertes sans mouvement depuis 48 h. On ne réécrit pas
    //    à propos de la même avant trois jours, sinon l'e-mail devient du bruit.
    $stale = $pdo->query(
        "SELECT d.id, d.title, d.amount, d.stage, d.updated_at,
                c.full_name, c.company, c.phone
           FROM crm_deals d
           LEFT JOIN crm_contacts c ON c.id = d.contact_id
          WHERE d.stage NOT IN ('gagne','perdu')
            AND d.followup_paused = 0
            AND d.updated_at < (NOW() - INTERVAL 48 HOUR)
            AND (d.last_followup_at IS NULL
                 OR d.last_followup_at < (NOW() - INTERVAL 3 DAY))
          ORDER BY d.updated_at ASC
          LIMIT 25"
    )->fetchAll(PDO::FETCH_ASSOC);

    // 3. Devis jamais ouverts : un devis non lu au bout de deux jours mérite
    //    un coup de fil, pas un second e-mail.
    $unopened = $pdo->query(
        "SELECT q.reference, q.service, q.amount, q.created_at, q.token,
                q.client_name, q.client_company, q.client_phone
           FROM crm_quotes q
          WHERE q.downloaded_at IS NULL
            AND q.created_at < (NOW() - INTERVAL 2 DAY)
            AND q.created_at > (NOW() - INTERVAL 30 DAY)
          ORDER BY q.created_at ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    if (!$overdue && !$stale && !$unopened) {
        echo json_encode(['status' => 'ok', 'sent' => false, 'reason' => 'rien à signaler']);
        exit;
    }

    // ------------------------------------------------------------- message
    $lines = ['Bonjour Dieylany,', ''];

    if ($overdue) {
        $lines[] = '⏰ RELANCES EN RETARD (' . count($overdue) . ')';
        foreach ($overdue as $r) {
            $lines[] = sprintf(
                '  · %s — %s%s',
                $r['title'],
                relance_who($r),
                $r['amount'] ? ' — ' . relance_money($r['amount']) : ''
            );
            $lines[] = sprintf(
                '    prévu le %s : %s',
                date('d/m', strtotime($r['next_action_at'])),
                $r['next_action'] ?: 'aucune action notée'
            );
        }
        $lines[] = '';
    }

    if ($stale) {
        $lines[] = '💤 SANS NOUVELLE DEPUIS 48 H (' . count($stale) . ')';
        foreach ($stale as $r) {
            $days = (int) floor((time() - strtotime($r['updated_at'])) / 86400);
            $lines[] = sprintf(
                '  · %s — %s — %s, %d jour%s sans mouvement',
                $r['title'],
                relance_who($r),
                $r['stage'],
                $days,
                $days > 1 ? 's' : ''
            );
        }
        $lines[] = '';
    }

    if ($unopened) {
        $lines[] = '📄 DEVIS JAMAIS OUVERTS (' . count($unopened) . ')';
        foreach ($unopened as $q) {
            $lines[] = sprintf(
                '  · %s — %s — %s — %s (envoyé le %s)',
                $q['reference'],
                $q['client_name'] ?: 'client inconnu',
                $q['service'],
                relance_money($q['amount']),
                date('d/m', strtotime($q['created_at']))
            );
            if ($q['client_phone']) {
                $lines[] = '    WhatsApp : https://wa.me/' . preg_replace('/\D+/', '', $q['client_phone']);
            }
        }
        $lines[] = '';
    }

    $lines[] = 'Le pipeline complet : https://dieylany.dev/admin/';
    $lines[] = '';
    $lines[] = '— MAX';

    $body = implode("\n", $lines);

    $subject = sprintf(
        'Pipeline SDS — %d relance%s en retard, %d en sommeil',
        count($overdue),
        count($overdue) > 1 ? 's' : '',
        count($stale)
    );

    $key = defined('RESEND_API_KEY') ? RESEND_API_KEY : '';
    if ($key === '') {
        echo json_encode(['status' => 'error', 'reason' => 'aucune clé Resend']);
        exit;
    }

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'from'    => defined('ALERT_FROM') ? ALERT_FROM : 'MAX <onboarding@resend.dev>',
            'to'      => [defined('ALERT_EMAIL') ? ALERT_EMAIL : 'dieylany.dev@gmail.com'],
            'subject' => $subject,
            'text'    => $body,
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $key,
        ],
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status < 200 || $status >= 300) {
        error_log("Relances : Resend a répondu $status — $response");
        // Le détail est utile pour diagnostiquer, et ce point d'entrée n'est
        // atteignable qu'avec le jeton : il n'y a rien de public ici.
        echo json_encode([
            'status'   => 'error',
            'http'     => $status,
            'resend'   => json_decode($response, true) ?? $response,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // On note l'envoi pour ne pas réécrire sur les mêmes avant trois jours.
    if ($stale) {
        $ids = array_column($stale, 'id');
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("UPDATE crm_deals SET last_followup_at = NOW() WHERE id IN ($in)")
            ->execute($ids);
    }

    echo json_encode([
        'status'   => 'ok',
        'sent'     => true,
        'overdue'  => count($overdue),
        'stale'    => count($stale),
        'unopened' => count($unopened),
    ]);
} catch (Throwable $e) {
    error_log('Relances : ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error']);
}
