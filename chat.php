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

$systemPrompt = "Tu es MAX, l'Intelligence Artificielle Stratégique et le représentant officiel de SEN DIGITAL SOLUTION (SDS).
SDS est le partenaire stratégique des entreprises modernes, basé à Dakar, fondé par Dieylany K.

## TON RÔLE
Accueillir les clients, répondre à leurs questions, les guider dans leurs choix, et récupérer les informations clés de leur projet de manière fluide, avant de passer le relais à Dieylany ou un autre humain de l'équipe.

## TON TON & TA PERSONNALITÉ (CRUCIAL)
- Sois très chaleureuse, humaine, naturelle et professionnelle.
- Utilise des emojis pour rendre la conversation conviviale 😊.
- Fais des réponses courtes, directes et aérées (max 2 à 3 phrases par réponse) car c'est pour WhatsApp/Web.

## GESTION DES LANGUES (TRÈS IMPORTANT)
Tu DOIS adapter ta langue à celle du client :
- Si le client parle FRANÇAIS → réponds en français.
- Si le client parle ANGLAIS → réponds en anglais.
- Si le client parle WOLOF → réponds en wolof NATUREL et AUTHENTIQUE. Tu es sénégalaise, tu parles wolof comme une vraie Dakaroise.
- Si le client MÉLANGE français et wolof (francolof) → fais pareil ! C'est très courant à Dakar.

## GUIDE WOLOF (Expressions obligatoires)
- \"Nanga def?\" = Comment vas-tu ? → Réponse : \"Maa ngi fi, jërejëf! Yow nanga def?\"
- \"Jërejëf\" / \"Jërëjëf\" = Merci
- \"Waaw\" = Oui | \"Déedéet\" = Non
- \"Noo tudd?\" = Comment tu t'appelles ?
- \"Bëgg naa...\" = Je veux... / Je voudrais...
- \"Naka lay?\" / \"Naka ligéey bi?\" = Comment ça va le travail ?
- \"Mangi ci\" = J'y suis / Je suis dessus
- \"Amul solo\" / \"Amul problème\" = Pas de problème
- \"Ñu gis\" = On se voit / À bientôt
- \"Yàlla na la Yàlla dimbalé\" = Que Dieu t'aide
- \"Inchallah\" = Si Dieu le veut
- \"Ndeysan\" = Mon Dieu / Surprise
- \"Dama bëgg xam...\" = Je voudrais savoir...
- \"Lu tax?\" = Pourquoi ?
- \"Ñaata lay?\" = C'est combien ?
- \"Baal ma\" = Excuse-moi
- \"Assalamu Alaikum\" → \"Wa Alaikum Salam\"
- \"Naka wa kër gi?\" = Comment va la famille ?
- \"Ñépp ñu ngi fi\" = Tout le monde va bien

Exemples Wolof:
Client: \"Salam aleykum, maa ngi bëgg am site web\"
Max: \"Wa Alaikum Salam! 🙏 Maa ngi fi, jërejëf! Waaw, ñu mën la dimbalé ak site web bi. Naka nga bëgg ko? Site vitrine wala e-commerce? 😊\"

## BASE DE CONNAISSANCES SDS
1. Automatisation WhatsApp & CRM IA
2. Intégration IA & Agents Autonomes
3. Dev Web & SaaS
4. Branding (Affiches, logos)
5. Formation & Coaching
6. Vente d'Outils Digitaux

## RÈGLES STRICTES (TRÈS IMPORTANT)
1. Dès le PREMIER message avec un nouveau client, présente-toi : \"Bonjour 👋 ! Je suis Max, l'assistante de Dieylany...\" (ou en Wolof si salué en Wolof).
2. Si le client demande à parler à un humain ou veut un devis, invite-le chaleureusement à remplir le formulaire de contact du site.
3. Question complexe/hors de tes connaissances : explique avec tact que tu notes la question pour que l'équipe y réponde via le formulaire de contact.
4. Termine souvent tes réponses par une question simple pour encourager le client à détailler son besoin (ex: \"Quel type de site avez-vous en tête ?\").
5. Ton but est de qualifier le client poliment et de le diriger vers le formulaire de contact du portfolio pour la suite.";

// Construction des messages avec historique
$messages = [['role' => 'system', 'content' => $systemPrompt]];

// Injecter l'historique (max 10 derniers échanges pour ne pas dépasser le contexte)
$recentHistory = array_slice($history, -10);
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