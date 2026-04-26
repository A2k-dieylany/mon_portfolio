<?php
session_start();
header('Content-Type: application/json');

// Rate limiting pour le formulaire : 3 requêtes max toutes les 5 minutes
$timeLimit = 300; // 5 minutes
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

// Vérifie si la requête est bien une requête POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et nettoyage des données
    $name = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $subject = htmlspecialchars(trim($_POST['subject'] ?? ''));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));

    // Vérification que tous les champs sont remplis et valides
    if (!$name || !$email || !$subject || !$message) {
        echo json_encode(['status' => 'error', 'message' => 'Veuillez remplir tous les champs correctement.']);
        exit;
    }

    // 1. Anti-spam : Vérification du Honeypot
    $website = trim($_POST['website'] ?? '');
    if (!empty($website)) {
        // C'est un bot, on feint le succès sans rien faire
        echo json_encode(['status' => 'success', 'message' => 'Message envoyé avec succès.']);
        exit;
    }

    // Paramètres de l'email
    $to = 'dieylanya2k@gmail.com'; 
    $email_subject = "Nouveau message de $name: $subject";
    $email_body = "Nom: $name\n";
    $email_body .= "Email: $email\n\n";
    $email_body .= "Message:\n$message\n";

    // En-têtes de l'email
    $headers = "From: $email\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    // 2. Sauvegarde en Base de Données (MySQL via PDO)
    try {
        $host = 'localhost';
        $dbname = 'portfolio_sds';
        $user = 'root';
        $pass = '';
        
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("INSERT INTO messages (name, email, subject, message) VALUES (:name, :email, :subject, :message)");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':subject' => $subject,
            ':message' => $message
        ]);
    } catch (PDOException $e) {
        // En cas d'erreur BDD (ex: MySQL éteint), on continue quand même l'envoi d'email
        // On pourrait logger l'erreur ici : error_log($e->getMessage());
    }

    // 3. Envoi de l'email
    if (mail($to, $email_subject, $email_body, $headers)) {
        echo json_encode(['status' => 'success', 'message' => 'Message envoyé avec succès.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erreur lors de l\'envoi du message. Vérifiez la configuration de votre serveur mail.']);
    }
} else {
    // Si la méthode n'est pas POST
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée.']);
}
?>
