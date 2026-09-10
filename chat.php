<?php
require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session_bootstrap.php';
sds_session_start();
header('Content-Type: application/json');
$groqApiKey = GROQ_API_KEY;

// Rate limiting DB: 10 requêtes max par minute par IP
$maxRequests = 10;
$timeLimit = 60;

$ip_hash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
$pdo = getDB();

// Nettoyer les anciens rate limits pour cet endpoint
$pdo->exec("DELETE FROM api_rate_limits WHERE window_start < (NOW() - INTERVAL $timeLimit SECOND)");

$stmt = $pdo->prepare("SELECT requests_count FROM api_rate_limits WHERE ip_hash = ? AND endpoint = 'chat'");
$stmt->execute([$ip_hash]);
$row = $stmt->fetch();

if ($row) {
    if ($row['requests_count'] >= $maxRequests) {
        echo json_encode(['reply' => "Vous avez envoyé trop de messages. Patientez une minute. / Too many messages, please wait. / لقد تجاوزت الحد، انتظر دقيقة."]);
        exit;
    }
    $pdo->prepare("UPDATE api_rate_limits SET requests_count = requests_count + 1 WHERE ip_hash = ? AND endpoint = 'chat'")->execute([$ip_hash]);
} else {
    $pdo->prepare("INSERT INTO api_rate_limits (ip_hash, endpoint, requests_count) VALUES (?, 'chat', 1)")->execute([$ip_hash]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['reply' => "Méthode non autorisée."]);
    exit;
}

// Validation Origin/Referer — bloquer les requêtes externes
$allowedHosts = ['localhost', '127.0.0.1', 'dieylany.dev', 'www.dieylany.dev', $_SERVER['HTTP_HOST'] ?? ''];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$sourceHost = '';

if (!empty($origin)) {
    $sourceHost = parse_url($origin, PHP_URL_HOST);
} elseif (!empty($referer)) {
    $sourceHost = parse_url($referer, PHP_URL_HOST);
}

if (!empty($sourceHost) && !in_array($sourceHost, $allowedHosts)) {
    echo json_encode(['reply' => "Accès non autorisé."]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($input['message'] ?? '');
$history     = $input['history'] ?? []; // historique envoyé depuis le front

if (empty($userMessage)) {
    echo json_encode(['reply' => "Message vide."]);
    exit;
}

// Limiter la longueur du message (anti-abus)
if (mb_strlen($userMessage) > 1000) {
    echo json_encode(['reply' => "Message trop long. Limitez à 1000 caractères."]);
    exit;
}

require_once __DIR__ . '/admin/includes/chat_context.php';

// Numéro de contact : réglable dans l'admin plutôt que codé en dur.
$whatsappNumber = '221780152522';
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1");
    $stmt->execute(['whatsapp_number']);
    if ($v = $stmt->fetchColumn()) {
        $whatsappNumber = preg_replace('/\D+/', '', $v) ?: $whatsappNumber;
    }
} catch (Throwable $e) {
    error_log('Chat : numéro WhatsApp — ' . $e->getMessage());
}

$systemPrompt = sds_chat_system_prompt($pdo, $whatsappNumber);

// Construction des messages avec historique
$messages = [['role' => 'system', 'content' => $systemPrompt]];

// L'historique fait autorité côté serveur : celui envoyé par le navigateur
// disparaît au rechargement de la page et reste modifiable par le visiteur.
$recentHistory = [];
try {
    $stmt = $pdo->prepare(
        "SELECT user_message, bot_response FROM chatbot_logs
          WHERE session_id = ? ORDER BY id DESC LIMIT 8"
    );
    $stmt->execute([session_id()]);
    foreach (array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC)) as $row) {
        $recentHistory[] = ['role' => 'user',      'content' => $row['user_message']];
        $recentHistory[] = ['role' => 'assistant', 'content' => $row['bot_response']];
    }
} catch (Throwable $e) {
    error_log('Chat : historique — ' . $e->getMessage());
}
// Repli sur ce qu'envoie le navigateur si la base est momentanément muette.
if (!$recentHistory) {
    $recentHistory = array_slice($history, -10);
}
foreach ($recentHistory as $turn) {
    $role    = ($turn['role'] === 'user') ? 'user' : 'assistant';
    $content = trim($turn['content'] ?? '');
    if (!empty($content)) {
        $messages[] = ['role' => $role, 'content' => $content];
    }
}

// Message courant
$messages[] = ['role' => 'user', 'content' => $userMessage];

$data = [
    'model'       => 'qwen/qwen3.8-27b',
    'messages'    => $messages,
    'max_tokens'  => 500,
    'temperature' => 0.7,
    'top_p'       => 0.9
];

$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $groqApiKey
]);

$response = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $res   = json_decode($response, true);
    $reply = $res['choices'][0]['message']['content'] ?? "Une erreur est survenue.";
    
    // Logger la conversation dans la base de données
    try {
        require_once __DIR__ . '/admin/includes/db.php';
        $dbLog = getDB();
        $sessionId = session_id();
        $ipHash = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0') . 'sds_salt_2025');
        $lang = 'fr';
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $userMessage)) $lang = 'ar';
        elseif (preg_match('/^[a-zA-Z\s\d\p{P}]+$/u', $userMessage)) $lang = 'en';
        
        $logStmt = $dbLog->prepare("INSERT INTO chatbot_logs (session_id, user_message, bot_response, language, ip_hash) VALUES (?, ?, ?, ?, ?)");
        $logStmt->execute([$sessionId, $userMessage, trim($reply), $lang, $ipHash]);

        // Détection de lead chaud : si le visiteur montre une intention d'achat,
        // on notifie une seule fois par conversation via le webhook WhatsApp.
        $leadKeywords = [
            'devis', 'tarif', 'tarifs', 'prix', 'combien ça coûte', 'combien coute', 'combien ça coute',
            'je veux commander', 'je voudrais commander', "j'aimerais commander", 'contactez-moi',
            'appelez-moi', 'rappelez-moi', 'rendez-vous', ' rdv ', 'parler à un humain',
            'parler à quelqu\'un', 'votre numéro', 'votre whatsapp', 'je suis intéressé',
            'je suis intéressée', 'je veux collaborer', 'démarrer un projet', 'lancer un projet',
            "j'ai un projet", 'mon budget', 'mon numéro est', 'mon email est', 'quote', 'pricing',
            'how much', 'call me', 'contact me', 'get in touch', "i'm interested", 'start a project',
            'my budget', 'my number is', 'my email is', 'bëgg naa', 'ñaata lay',
        ];
        $haystack = mb_strtolower($userMessage);
        foreach ($recentHistory as $turn) {
            if (($turn['role'] ?? '') === 'user') {
                $haystack .= ' ' . mb_strtolower(trim($turn['content'] ?? ''));
            }
        }

        $isLead = false;
        foreach ($leadKeywords as $kw) {
            if (mb_strpos($haystack, $kw) !== false) {
                $isLead = true;
                break;
            }
        }

        if ($isLead) {
            $insertLead = $dbLog->prepare("INSERT IGNORE INTO chatbot_leads (session_id) VALUES (?)");
            $insertLead->execute([$sessionId]);

            // Le prospect entre dans le CRM avec sa conversation complète.
            // Jusqu'ici seul le session_id était conservé : le numéro qu'un
            // visiteur tapait dans le chat n'était visible nulle part.
            $transcript = '';
            foreach ($recentHistory as $turn) {
                $who = ($turn['role'] ?? '') === 'user' ? 'Visiteur' : 'MAX';
                $transcript .= "$who : " . trim($turn['content'] ?? '') . "
";
            }
            $transcript .= "Visiteur : $userMessage
MAX : " . trim($reply);

            require_once __DIR__ . '/admin/includes/crm_capture.php';
            require_once __DIR__ . '/admin/includes/chat_extract.php';

            $leadPhone = sds_crm_find_phone($haystack);
            $leadEmail = sds_crm_find_email($haystack);

            $dealId = sds_crm_capture($dbLog, [
                'name'       => 'Prospect chatbot',
                'email'      => $leadEmail,
                'phone'      => $leadPhone,
                'title'      => 'Conversation chatbot' . ($leadPhone ? " — $leadPhone" : ''),
                'source'     => 'chatbot',
                'summary'    => $transcript,
                'source_ref' => 'chat:' . $sessionId,
            ]);

            // Seconde lecture de la conversation : elle en tire le nom, le
            // numéro, le service visé et le besoin résumé, que les expressions
            // régulières seules ne savent pas déduire.
            if ($dealId) {
                $facts = sds_chat_extract($transcript, $groqApiKey, 'qwen/qwen3.8-27b');
                if ($facts) {
                    $extractedPhone = $facts['phone']
                        ? (sds_crm_find_phone($facts['phone']) ?: $facts['phone'])
                        : null;

                    // Le résumé du besoin passe en tête ; la transcription
                    // complète reste dessous, pour pouvoir tout relire.
                    $summary = null;
                    if ($facts['need']) {
                        $parts = [$facts['need']];
                        if ($facts['budget']) {
                            $parts[] = 'Budget évoqué : ' . $facts['budget'];
                        }
                        $parts[] = '--- Conversation ---';
                        $parts[] = $transcript;
                        $summary = implode(PHP_EOL . PHP_EOL, $parts);
                    }

                    sds_crm_enrich($dbLog, $dealId, [
                        'name'    => $facts['name'],
                        'company' => $facts['company'],
                        'email'   => $facts['email'],
                        'phone'   => $extractedPhone ?: $leadPhone,
                        'title'   => $facts['service'] ? 'Chatbot — ' . $facts['service'] : null,
                        'summary' => $summary,
                    ]);
                }
            }

            // rowCount() = 1 seulement si la ligne vient d'être insérée (pas déjà notifiée)
            if ($insertLead->rowCount() > 0) {
                $webhookUrl = defined('WEBHOOK_URL') ? WEBHOOK_URL : '';
                if (!empty($webhookUrl)) {
                    $conversationText = '';
                    foreach ($recentHistory as $turn) {
                        $who = ($turn['role'] ?? '') === 'user' ? 'Visiteur' : 'MAX';
                        $conversationText .= "$who: " . trim($turn['content'] ?? '') . "\n";
                    }
                    $conversationText .= "Visiteur: $userMessage\nMAX: " . trim($reply);

                    $webhookData = json_encode([
                        'source'  => 'sds_chatbot',
                        'name'    => 'Visiteur Chatbot MAX',
                        'email'   => 'Non fourni (via chatbot)',
                        'subject' => 'Lead détecté par le chatbot MAX',
                        'message' => $conversationText,
                        'timestamp' => date('c'),
                    ]);

                    $chWebhook = curl_init($webhookUrl);
                    curl_setopt($chWebhook, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($chWebhook, CURLOPT_POST, true);
                    curl_setopt($chWebhook, CURLOPT_POSTFIELDS, $webhookData);
                    curl_setopt($chWebhook, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    curl_setopt($chWebhook, CURLOPT_TIMEOUT, 3);
                    curl_exec($chWebhook);
                    curl_close($chWebhook);
                }
            }
        }
    } catch (Exception $e) {
        error_log("Chatbot log error: " . $e->getMessage());
    }

    echo json_encode(['reply' => trim($reply)]);
} else {
    error_log("Groq API error $httpCode: $response");
    echo json_encode(['reply' => "Je suis temporairement indisponible. Contactez-nous via le formulaire du site ou sur WhatsApp au +221 78 015 25 22."]);
}
?>