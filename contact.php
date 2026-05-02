<?php
session_start();
header('Content-Type: application/json');

// ===== Rate limiting : 3 requêtes max toutes les 5 minutes =====
$timeLimit = 300;
$maxRequests = 3;

if (!isset($_SESSION['contact_requests'])) {
    $_SESSION['contact_requests'] = [];
}
$currentTime = time();
$_SESSION['contact_requests'] = array_filter($_SESSION['contact_requests'], function($ts) use ($currentTime, $timeLimit) {
    return ($currentTime - $ts) < $timeLimit;
});

if (count($_SESSION['contact_requests']) >= $maxRequests) {
    echo json_encode(['status' => 'error', 'message' => "Trop de tentatives. Veuillez réessayer plus tard."]);
    exit;
}
$_SESSION['contact_requests'][] = $currentTime;

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

// Échappement HTML pour stockage/affichage
$safeName    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
$safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

// ===== Paramètres de l'email =====
$to = 'dieylanya2k@gmail.com';
$email_subject = "Nouveau message de $safeName: $safeSubject";
$email_body  = "Nom: $safeName\n";
$email_body .= "Email: $email\n\n";
$email_body .= "Message:\n$safeMessage\n";

// En-têtes sécurisées : From utilise une adresse du serveur, Reply-To l'adresse du visiteur
$headers  = "From: noreply@sds.sn\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// ===== Sauvegarde en Base de Données (MySQL via PDO) =====
try {
    require_once __DIR__ . '/config.php';
    $host   = defined('DB_HOST') ? DB_HOST : 'localhost';
    $dbname = defined('DB_NAME') ? DB_NAME : 'portfolio_sds';
    $user   = defined('DB_USER') ? DB_USER : 'root';
    $pass   = defined('DB_PASS') ? DB_PASS : '';

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("INSERT INTO messages (name, email, subject, message) VALUES (:name, :email, :subject, :message)");
    $stmt->execute([
        ':name'    => $safeName,
        ':email'   => $email,
        ':subject' => $safeSubject,
        ':message' => $safeMessage
    ]);
} catch (PDOException $e) {
    error_log("SDS Contact DB Error: " . $e->getMessage());
    // On continue l'envoi d'email même si la DB échoue
}

// ===== Envoi de l'email =====
if (mail($to, $email_subject, $email_body, $headers)) {
    echo json_encode(['status' => 'success', 'message' => 'Message envoyé avec succès.']);
} else {
    error_log("SDS Contact Mail Error: mail() failed for $email");
    echo json_encode(['status' => 'error', 'message' => "Erreur lors de l'envoi. Réessayez ou contactez-nous via WhatsApp."]);
}
?>
