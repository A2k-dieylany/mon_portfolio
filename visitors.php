<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$host   = defined('DB_HOST') ? DB_HOST : 'localhost';
$dbname = defined('DB_NAME') ? DB_NAME : 'portfolio_sds';
$user   = defined('DB_USER') ? DB_USER : 'root';
$pass   = defined('DB_PASS') ? DB_PASS : '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Créer la table si elle n'existe pas
    $pdo->exec("CREATE TABLE IF NOT EXISTS visitors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_hash VARCHAR(64) NOT NULL,
        country VARCHAR(50) DEFAULT '',
        visited_at DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_visit (ip_hash, visited_at)
    )");

    // Hash de l'IP pour la vie privée (on ne stocke jamais l'IP en clair)
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ipHash = hash('sha256', $ip . 'sds_salt_2025');
    $today = date('Y-m-d');

    // Enregistrer la visite (IGNORE si déjà visité aujourd'hui grâce à la clé unique)
    $stmt = $pdo->prepare("INSERT IGNORE INTO visitors (ip_hash, visited_at) VALUES (:hash, :today)");
    $stmt->execute([':hash' => $ipHash, ':today' => $today]);

    // Compter les statistiques
    $total = $pdo->query("SELECT COUNT(*) FROM visitors")->fetchColumn();
    $todayCount = $pdo->prepare("SELECT COUNT(*) FROM visitors WHERE visited_at = :today");
    $todayCount->execute([':today' => $today]);
    $todayVisitors = $todayCount->fetchColumn();

    // Visiteurs cette semaine
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $weekStmt = $pdo->prepare("SELECT COUNT(*) FROM visitors WHERE visited_at >= :week");
    $weekStmt->execute([':week' => $weekStart]);
    $weekVisitors = $weekStmt->fetchColumn();

    echo json_encode([
        'total'   => (int)$total,
        'today'   => (int)$todayVisitors,
        'week'    => (int)$weekVisitors,
        'status'  => 'ok'
    ]);

} catch (PDOException $e) {
    error_log("SDS Visitors Error: " . $e->getMessage());
    echo json_encode([
        'total'  => 0,
        'today'  => 0,
        'week'   => 0,
        'status' => 'error'
    ]);
}
?>
