<?php
require_once __DIR__ . '/session_bootstrap.php';
sds_session_start();
header('Content-Type: application/json');



// ===== Vérification méthode POST =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée.']);
    exit;
}

// ===== Vérification CSRF =====
$token = $_POST['csrf_token'] ?? '';
if (empty($token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    echo json_encode(['status' => 'error', 'message' => 'Token de sécurité invalide. Rechargez la page.']);
    exit;
}
// Invalider le token après usage (usage unique)
unset($_SESSION['csrf_token']);

require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/config.php';

// ===== Rate limiting DB : 3 requêtes max toutes les 5 minutes =====
$timeLimit = 300;
$maxRequests = 3;

$ip_hash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
$pdo = getDB();
$pdo->exec("DELETE FROM api_rate_limits WHERE window_start < (NOW() - INTERVAL $timeLimit SECOND)");

$stmt = $pdo->prepare("SELECT requests_count FROM api_rate_limits WHERE ip_hash = ? AND endpoint = 'contact'");
$stmt->execute([$ip_hash]);
$row = $stmt->fetch();

if ($row) {
    if ($row['requests_count'] >= $maxRequests) {
        echo json_encode(['status' => 'error', 'message' => "Trop de tentatives. Veuillez réessayer plus tard."]);
        exit;
    }
    $pdo->prepare("UPDATE api_rate_limits SET requests_count = requests_count + 1 WHERE ip_hash = ? AND endpoint = 'contact'")->execute([$ip_hash]);
} else {
    $pdo->prepare("INSERT INTO api_rate_limits (ip_hash, endpoint, requests_count) VALUES (?, 'contact', 1)")->execute([$ip_hash]);
}

// ===== Anti-spam : Honeypot =====
$website = trim($_POST['website'] ?? '');
if (!empty($website)) {
    echo json_encode(['status' => 'success', 'message' => 'Message envoyé avec succès.']);
    exit;
}

// ===== Récupération et nettoyage des données =====
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// ===== Validation de longueur =====
if (mb_strlen($name) > 100 || mb_strlen($email) > 150 || mb_strlen($subject) > 200 || mb_strlen($message) > 5000) {
    echo json_encode(['status' => 'error', 'message' => 'Un ou plusieurs champs dépassent la longueur maximale autorisée.']);
    exit;
}

// ===== Validation des champs =====
if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    echo json_encode(['status' => 'error', 'message' => 'Veuillez remplir tous les champs correctement.']);
    exit;
}

// Validation stricte de l'email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Adresse email invalide.']);
    exit;
}

// ===== Protection contre l'injection d'en-têtes (suppression des retours à la ligne) =====
$name    = str_replace(["\r", "\n", "\t"], '', $name);
$email   = str_replace(["\r", "\n", "\t"], '', $email);
$subject = str_replace(["\r", "\n", "\t"], '', $subject);

// ===== Sauvegarde en Base de Données (MySQL via PDO) =====
// NOTE : on stocke les données BRUTES — l'échappement HTML se fait uniquement à l'affichage
try {
    $stmt = $pdo->prepare("INSERT INTO messages (name, email, subject, message) VALUES (:name, :email, :subject, :message)");
    $stmt->execute([
        ':name'    => $name,
        ':email'   => $email,
        ':subject' => $subject,
        ':message' => $message
    ]);
} catch (PDOException $e) {
    error_log("SDS Contact DB Error: " . $e->getMessage());
    // On continue l'envoi d'email même si la DB échoue
}

// ===== Envoi de l'email via API Resend =====
$resendApiKey = defined('RESEND_API_KEY') ? RESEND_API_KEY : '';

if (empty($resendApiKey) || $resendApiKey === 'YOUR_RESEND_API_KEY') {
    error_log("SDS Contact Error: Clé API Resend non configurée dans .env");
    echo json_encode(['status' => 'error', 'message' => "Erreur de configuration serveur. Contactez-nous via WhatsApp."]);
    exit;
}

$postData = json_encode([
    'from' => 'onboarding@resend.dev', // Utiliser l'email par défaut de Resend pour le testing. À changer par un domaine vérifié en prod.
    'to' => ['dieylany.dev@gmail.com'], 
    'subject' => "Nouveau message de $name: $subject",
    'html' => "<p><strong>Nom:</strong> " . htmlspecialchars($name) . "</p><p><strong>Email:</strong> " . htmlspecialchars($email) . "</p><p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>",
    'reply_to' => filter_var($email, FILTER_SANITIZE_EMAIL)
]);

$ch = curl_init('https://api.resend.com/emails');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $resendApiKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    // ===== Envoi au Webhook CRM (n8n / Make) =====
    $webhookUrl = defined('WEBHOOK_URL') ? WEBHOOK_URL : '';
    if (!empty($webhookUrl)) {
        $webhookData = json_encode([
            'source' => 'sds_portfolio_contact_form',
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
            'timestamp' => date('c')
        ]);

        $chWebhook = curl_init($webhookUrl);
        curl_setopt($chWebhook, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chWebhook, CURLOPT_POST, true);
        curl_setopt($chWebhook, CURLOPT_POSTFIELDS, $webhookData);
        curl_setopt($chWebhook, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($chWebhook, CURLOPT_TIMEOUT, 3); // Timeout de 3s pour ne pas ralentir la réponse frontend
        curl_exec($chWebhook);
        curl_close($chWebhook);
    }

    echo json_encode(['status' => 'success', 'message' => 'Message envoyé avec succès.']);
} else {
    error_log("SDS Contact Mail Error API: HTTP $httpCode - Response: $response");
    echo json_encode(['status' => 'error', 'message' => "Erreur lors de l'envoi de l'email. Réessayez ou contactez-nous via WhatsApp."]);
}
?>
