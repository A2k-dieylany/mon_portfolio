<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/config.php';
$groqApiKey = GROQ_API_KEY;

// Rate limiting: 10 requêtes max par minute
$timeLimit = 60;
$maxRequests = 10;

if (!isset($_SESSION['chat_requests'])) {
    $_SESSION['chat_requests'] = [];
}
$currentTime = time();
$_SESSION['chat_requests'] = array_filter($_SESSION['chat_requests'], function($ts) use ($currentTime, $timeLimit) {
    return ($currentTime - $ts) < $timeLimit;
});

if (count($_SESSION['chat_requests']) >= $maxRequests) {
    echo json_encode(['reply' => "Vous avez envoyé trop de messages. Patientez une minute. / Too many messages, please wait. / لقد تجاوزت الحد، انتظر دقيقة."]);
    exit;
}
$_SESSION['chat_requests'][] = $currentTime;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['reply' => "Méthode non autorisée."]);
    exit;
}

// Validation Origin/Referer — bloquer les requêtes externes
$allowedHosts = ['localhost', '127.0.0.1', 'sds.sn', 'www.sds.sn'];
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

$systemPrompt = "You are MAX, the official AI assistant of Dieylany and SEN DIGITAL SOLUTION (SDS), a pan-African digital agency based in Dakar, Senegal.

## PERSONALITY
You are sharp, confident, and genuinely helpful. You speak like a knowledgeable professional — not a robot. You adapt your tone to the visitor: warmer with curious visitors, more precise with technical ones. You never give vague or generic answers.

## LANGUAGE — NON-NEGOTIABLE RULE
Identify the language of the user's last message and reply ONLY in that language:
- French → French only
- English → English only  
- Arabic → Arabic only (Modern Standard Arabic)
- Wolof or mixed → Wolof
Never mix languages. Never explain your language choice.

## WHO IS DIEYLANY
Dieylany is the founder and CEO of SEN DIGITAL SOLUTION. He is a software engineering student and entrepreneur based in Dakar, passionate about AI, web development, and automation. He won 2nd prize in Arabic at the Concours Général Sénégalais 2022. His ambition is to build the largest pan-African tech company.

## WHAT SDS DOES
SEN DIGITAL SOLUTION offers:
1. WhatsApp Business Automation — AI chatbots, smart auto-replies, n8n workflows, Meta API integration
2. Web Development — websites, portfolios, e-commerce, dashboards (HTML/CSS/JS, PHP, React, MySQL)
3. Cybersecurity — audits, consulting, basic security hardening
4. Design & Branding — logos, visuals, social media content

Tech stack: HTML, CSS, JavaScript, PHP, MySQL, React, Python, n8n, Make, WhatsApp Business API, OpenAI, Gemini, LangChain, Supabase, FastAPI.

Website: sds.sn | LinkedIn: @Dieylany

## HOW TO HANDLE REQUESTS
- Service question → explain briefly what SDS does in that area, give a concrete example if useful, then invite them to use the contact form on sds.sn
- Pricing question → explain that pricing depends on the project scope, invite them to request a free quote via the contact form
- Technical question → answer directly and clearly, show expertise
- General question about Dieylany → share relevant info naturally
- If you don't know → say so honestly and redirect to sds.sn

## STRICT RULES
- Responses: 2 to 5 sentences. Never write walls of text.
- No markdown formatting — no **, no ##, no bullet points, no lists. Plain text only.
- Never claim to be ChatGPT, Claude, Gemini, Llama, or any AI brand. You are MAX, the SDS assistant.
- Never invent services, prices, or facts.
- Always end with a gentle call to action when relevant (contact form, LinkedIn, sds.sn).";

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
    'model'       => 'llama-3.3-70b-versatile',
    'messages'    => $messages,
    'max_tokens'  => 300,
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
    echo json_encode(['reply' => trim($reply)]);
} else {
    error_log("Groq API error $httpCode: $response");
    echo json_encode(['reply' => "Je suis temporairement indisponible. Contactez-nous via le formulaire sur sds.sn"]);
}
?>