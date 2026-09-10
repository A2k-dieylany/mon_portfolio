<?php
/**
 * Actions déclenchées par MAX.
 *
 * MAX termine sa réponse par des balises que le code intercepte et retire
 * avant affichage. C'est du function calling sans dépendre du support du
 * modèle : n'importe quel modèle sait écrire « [DEVIS:Site vitrine:150000] ».
 *
 * Placé sous includes/ : le dispatcher refuse d'y servir un fichier en HTTP.
 */

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/crm_capture.php';

/**
 * Crée un devis et renvoie sa ligne complète, prête à être mise en forme.
 *
 * @return array{reference:string,token:string,url:string,amount:float}|null
 */
function sds_create_quote(PDO $pdo, ?int $dealId, string $service, float $amount, array $client): ?array
{
    try {
        if (!$pdo->query("SHOW TABLES LIKE 'crm_quotes'")->fetchColumn()) {
            error_log('Devis : la table crm_quotes est absente.');
            return null;
        }

        // Un même échange ne doit pas produire dix devis si le modèle répète
        // la balise : on réutilise celui du jour pour ce montant et ce service.
        if ($dealId) {
            $stmt = $pdo->prepare(
                'SELECT reference, token, amount FROM crm_quotes
                  WHERE deal_id = ? AND service = ? AND amount = ?
                    AND created_at > (NOW() - INTERVAL 1 DAY)
                  LIMIT 1'
            );
            $stmt->execute([$dealId, $service, $amount]);
            if ($existing = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return [
                    'reference' => $existing['reference'],
                    'token'     => $existing['token'],
                    'url'       => 'https://dieylany.dev/devis/' . $existing['token'],
                    'amount'    => (float) $existing['amount'],
                ];
            }
        }

        $token = bin2hex(random_bytes(16));

        // Numérotation continue par année, lisible par le client.
        $year = date('Y');
        $next = (int) $pdo->query(
            "SELECT COUNT(*) FROM crm_quotes WHERE YEAR(created_at) = $year"
        )->fetchColumn() + 1;
        $reference = sprintf('SDS-%s-%04d', $year, $next);

        $stmt = $pdo->prepare(
            'INSERT INTO crm_quotes
                (reference, token, deal_id, client_name, client_company,
                 client_phone, client_email, service, amount, valid_until)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 30 DAY))'
        );
        $stmt->execute([
            $reference,
            $token,
            $dealId,
            $client['name']    ?? null,
            $client['company'] ?? null,
            $client['phone']   ?? null,
            $client['email']   ?? null,
            mb_substr($service, 0, 200),
            $amount,
        ]);

        // Un devis émis fait avancer l'opportunité dans le pipeline.
        if ($dealId) {
            $pdo->prepare("UPDATE crm_deals SET stage = 'devis' WHERE id = ? AND stage IN ('nouveau','contacte')")
                ->execute([$dealId]);
            $pdo->prepare(
                "INSERT INTO crm_activities (deal_id, kind, body, author)
                 VALUES (?, 'systeme', ?, 'MAX')"
            )->execute([$dealId, "Devis $reference émis : $service — " . number_format($amount, 0, ',', ' ') . ' FCFA']);
        }

        return [
            'reference' => $reference,
            'token'     => $token,
            'url'       => 'https://dieylany.dev/devis/' . $token,
            'amount'    => $amount,
        ];
    } catch (Throwable $e) {
        error_log('Devis (création) : ' . $e->getMessage());
        return null;
    }
}

/**
 * Prévient Dieylany par e-mail qu'un prospect demande une suite immédiate.
 */
function sds_alert_owner(string $subject, string $body): void
{
    $key = defined('RESEND_API_KEY') ? RESEND_API_KEY : '';
    if ($key === '') {
        error_log('Alerte prospect : aucune clé Resend configurée.');
        return;
    }

    $payload = json_encode([
        'from'    => defined('ALERT_FROM') ? ALERT_FROM : 'MAX <onboarding@resend.dev>',
        'to'      => [defined('ALERT_EMAIL') ? ALERT_EMAIL : 'dieylany.dev@gmail.com'],
        'subject' => $subject,
        'text'    => $body,
    ]);

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $key,
        ],
        CURLOPT_TIMEOUT => 6,
    ]);
    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status < 200 || $status >= 300) {
        error_log("Alerte prospect : Resend a répondu $status — $response");
    }
}

/**
 * Traite les balises émises par MAX et les retire de la réponse affichée.
 *
 * Renvoie deux versions du texte :
 *   - reply     : ce que voit le visiteur, liens du système compris ;
 *   - reply_log : le seul texte du modèle, à conserver dans l'historique.
 *
 * La distinction n'est pas cosmétique. En enregistrant le lien de devis dans
 * l'historique, MAX le relisait au tour suivant, en apprenait le format et
 * finissait par en fabriquer un de toutes pièces — envoyant au prospect une
 * URL qui ne mène nulle part. Constaté en production.
 *
 * @return array{reply:string,reply_log:string,quote:?array,hot:bool,human:bool,finished:bool}
 */
function sds_run_chat_actions(PDO $pdo, string $reply, ?int $dealId, array $client, string $transcript): array
{
    $hot = $human = $finished = false;
    $quote = null;
    $additions = [];

    // Filet de sécurité : si le modèle a écrit lui-même une URL de devis, elle
    // est forcément inventée — seul ce code sait générer un jeton valide.
    // On retire la ligne entière, sinon il reste une phrase en suspens
    // (« Votre devis SDS-2026-0002 est prêt : »).
    $reply = preg_replace('#^.*https?://\S*?/devis/[a-f0-9]{32}\S*.*$#im', '', $reply);

    // --- Devis : [DEVIS:Nom du service:150000]
    if (preg_match('/\[DEVIS\s*:\s*([^:\]]+?)\s*:\s*([0-9\s]+)\]/u', $reply, $m)) {
        $service = trim($m[1]);
        $amount  = (float) preg_replace('/\s+/', '', $m[2]);
        $reply   = str_replace($m[0], '', $reply);

        // Garde-fou : un montant aberrant trahit une hallucination du modèle.
        if ($amount >= 5000 && $amount <= 20000000) {
            $quote = sds_create_quote($pdo, $dealId, $service, $amount, $client);
            if ($quote) {
                $additions[] = '📄 Votre devis ' . $quote['reference']
                    . ' est prêt : ' . $quote['url'];
            }
        } else {
            error_log("Devis refusé : montant hors bornes ($amount).");
        }
    }

    // --- Rendez-vous
    if (str_contains($reply, '[RDV]') || str_contains($reply, '[LIEN_CALENDRIER]')) {
        $reply = str_replace(['[RDV]', '[LIEN_CALENDRIER]'], '', $reply);
        $additions[] = '📅 Choisissez un créneau : https://wa.me/221780152522';
    }

    // --- Prospect chaud / demande d'humain
    if (str_contains($reply, '[ALERTE_PROSPECT]')) {
        $hot   = true;
        $reply = str_replace('[ALERTE_PROSPECT]', '', $reply);
    }
    if (str_contains($reply, '[ALERTE_HUMAIN]')) {
        $human = true;
        $reply = str_replace('[ALERTE_HUMAIN]', '', $reply);
    }

    // --- Fin de discussion : évite de relancer quelqu'un qui a dit au revoir.
    if (str_contains($reply, '[FIN_DISCUSSION]')) {
        $finished = true;
        $reply    = str_replace('[FIN_DISCUSSION]', '', $reply);
        if ($dealId) {
            try {
                $pdo->prepare('UPDATE crm_deals SET followup_paused = 1 WHERE id = ?')
                    ->execute([$dealId]);
            } catch (Throwable $e) {
                error_log('Fin de discussion : ' . $e->getMessage());
            }
        }
    }

    if (($hot || $human) && $dealId) {
        $who = trim(($client['name'] ?? '') . ' ' . ($client['company'] ?? '')) ?: 'Prospect';
        sds_alert_owner(
            ($hot ? '🔥 Prospect chaud' : '🙋 Demande à parler à un humain') . ' — ' . $who,
            "Contact : " . ($client['phone'] ?? 'non communiqué')
            . ' · ' . ($client['email'] ?? 'pas d\'e-mail') . "\n"
            . ($quote ? "Devis émis : {$quote['reference']} ({$quote['url']})\n" : '')
            . "\nFiche : https://dieylany.dev/admin/ (Prospects & Clients, opportunité #$dealId)\n"
            . "\n--- Conversation ---\n" . $transcript
        );
        try {
            $pdo->prepare(
                "INSERT INTO crm_activities (deal_id, kind, body, author)
                 VALUES (?, 'systeme', ?, 'MAX')"
            )->execute([$dealId, $hot ? 'Prospect signalé comme chaud.' : 'A demandé à parler à un humain.']);
        } catch (Throwable $e) {
            error_log('Alerte (journal) : ' . $e->getMessage());
        }
    }

    // Les balises inconnues ne doivent jamais s'afficher au visiteur.
    $reply = preg_replace('/\[[A-Z_]+(?::[^\]]*)?\]/u', '', $reply);

    $modelText = trim(preg_replace('/\n{3,}/', "\n\n", $reply));

    return [
        'reply'     => $additions
            ? $modelText . "\n\n" . implode("\n", $additions)
            : $modelText,
        'reply_log' => $modelText,
        'quote'     => $quote,
        'hot'       => $hot,
        'human'     => $human,
        'finished'  => $finished,
    ];
}
