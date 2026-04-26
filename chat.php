<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();
session_start();
header('Content-Type: application/json');

// Chargement sécurisé de la clé API
require_once __DIR__ . '/config.php';
$groqApiKey = GROQ_API_KEY;

// Rate limiting: 5 requêtes max par minute
$timeLimit = 60;
$maxRequests = 5;

if (!isset($_SESSION['chat_requests'])) {
    $_SESSION['chat_requests'] = [];
}
$currentTime = time();
$_SESSION['chat_requests'] = array_filter($_SESSION['chat_requests'], function($ts) use ($currentTime, $timeLimit) {
    return ($currentTime - $ts) < $timeLimit;
});

if (count($_SESSION['chat_requests']) >= $maxRequests) {
    echo json_encode(['reply' => "Vous avez envoyé trop de messages. Veuillez patienter une minute. / Too many requests. Please wait a minute. / لقد أرسلت رسائل كثيرة، يرجى الانتظار دقيقة."]);
    exit;
}
$_SESSION['chat_requests'][] = $currentTime;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['reply' => "Méthode non autorisée."]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';

if (empty($userMessage)) {
    echo json_encode(['reply' => "Message vide."]);
    exit;
}

$systemPrompt = "You are the official AI assistant of Dieylany, founder and CEO of SEN DIGITAL SOLUTION (SDS), a pan-African digital agency based in Dakar, Senegal.

## LANGUAGE RULE — CRITICAL
Detect the language of the visitor's message and respond EXCLUSIVELY in that language:
- French message → respond in French
- English message → respond in English
- Arabic message → respond in Arabic (use Modern Standard Arabic, right-to-left)
- Mixed or ambiguous → respond in French by default
Never mix languages in a single response.

## YOUR IDENTITY
You are a professional, warm, and intelligent assistant representing Dieylany and SDS.
You speak on behalf of Dieylany to his potential clients and visitors.

## ABOUT DIEYLANY
- Full name: Dieylany
- Role: Founder & CEO of SEN DIGITAL SOLUTION (SDS)
- Location: Dakar, Senegal
- Vision: Build the leading pan-African tech company
- Languages: French, Arabic, English
- Awards: 2nd prize in Arabic — Concours Général Sénégalais 2022

## ABOUT SEN DIGITAL SOLUTION (SDS)
- Website: sds.sn
- Services:
  1. WhatsApp Business automation (chatbots, AI agents, n8n workflows)
  2. Web development (HTML/CSS/JS, PHP, React, MySQL)
  3. Cybersecurity consulting
  4. Graphic design & branding
- Tech stack: HTML, CSS, JavaScript, PHP, MySQL, React, Python, n8n, Make, WhatsApp API, OpenAI, Gemini, LangChain, Supabase, FastAPI
- Target clients: Senegalese and African businesses

## RESPONSE RULES
- Be concise: 2 to 4 sentences maximum
- Be professional yet warm — you represent a serious brand
- Never invent services or prices that are not listed above
- If asked about pricing or availability → invite them to use the contact form on the site
- If asked about a service → briefly describe it and redirect to the contact form
- If asked who you are → explain you are the AI assistant of Dieylany / SDS
- Never say you are ChatGPT, Claude, Groq, or any AI brand — you are the SDS Assistant
- Do not use markdown formatting (no **, no ##, no bullet points) — plain text only

## CONTACT
- Website: sds.sn
- LinkedIn: @Dieylany
- Contact form: available on sds.sn";

$data = [
    'model' => 'llama-3.3-70b-versatile',
    'messages' => [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user',   'content' => $userMessage]
    ],
    'max_tokens' => 200,
    'temperature' => 0.65
];

try {
    if (!function_exists('curl_init')) {
        throw new Exception("L'extension CURL n'est pas activée sur ce serveur.");
    }

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $groqApiKey
    ]);
    
    // Désactiver la vérification SSL pour XAMPP/localhost si nécessaire
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode == 200) {
        $res = json_decode($response, true);
        $reply = $res['choices'][0]['message']['content'] ?? "Une erreur est survenue lors de la récupération de la réponse.";
        ob_clean();
        echo json_encode(['reply' => trim($reply)]);
    } else {
        error_log("Groq API error $httpCode: $response. Curl Error: $curlError");
        $errorMsg = ($httpCode == 401) ? "Clé API invalide ou manquante." : "Service temporairement indisponible.";
        ob_clean();
        echo json_encode(['reply' => "Désolé, je rencontre une difficulté technique ($errorMsg). Veuillez utiliser le formulaire de contact sur sds.sn"]);
    }
} catch (Exception $e) {
    error_log("Chat Error: " . $e->getMessage());
    ob_clean();
    echo json_encode(['reply' => "Erreur interne du serveur : " . $e->getMessage()]);
}
?>