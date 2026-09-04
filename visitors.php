<?php
/**
 * SDS — Compteur de visiteurs publique
 * Utilise la connexion PDO partagée via getDB()
 */
header('Content-Type: application/json');
require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/session_bootstrap.php';

try {
    $pdo = getDB();

    // Hash de l'IP pour la vie privée (on ne stocke jamais l'IP en clair)
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ipHash = hash('sha256', $ip . 'sds_salt_2025');
    $today = date('Y-m-d');

    // Enregistrer la visite (IGNORE si déjà visité aujourd'hui grâce à la clé unique)
    $stmt = $pdo->prepare("INSERT IGNORE INTO visitors (ip_hash, visited_at) VALUES (:hash, :today)");
    $stmt->execute([':hash' => $ipHash, ':today' => $today]);

    // Enregistrer la vue de page
    sds_session_start();
    $session_id = session_id();
    
    $page = $_GET['page'] ?? 'home';
    if ($page === '' || $page === 'index.php') $page = 'home';
    $ref = $_GET['ref'] ?? '';
    $device = $_GET['device'] ?? 'desktop';

    $stmtView = $pdo->prepare("INSERT INTO page_views (session_id, page, referrer, device) VALUES (:sess, :page, :ref, :device)");
    $stmtView->execute([':sess' => $session_id, ':page' => $page, ':ref' => $ref, ':device' => $device]);

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
