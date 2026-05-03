<?php
/**
 * SDS Admin API — Overview KPIs
 */
session_start();
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

require_auth();
security_headers();
require_get();

try {
    $pdo = getDB();
    $today = date('Y-m-d');
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $monthStart = date('Y-m-01');

    // Visiteurs
    $totalVisitors = $pdo->query("SELECT COUNT(*) FROM visitors")->fetchColumn();
    $todayVisitors = $pdo->prepare("SELECT COUNT(*) FROM visitors WHERE visited_at = ?");
    $todayVisitors->execute([$today]);
    $todayVisitors = $todayVisitors->fetchColumn();

    $weekVisitors = $pdo->prepare("SELECT COUNT(*) FROM visitors WHERE visited_at >= ?");
    $weekVisitors->execute([$weekStart]);
    $weekVisitors = $weekVisitors->fetchColumn();

    // Messages
    $totalMessages = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
    $unreadMessages = $pdo->query("SELECT COUNT(*) FROM messages WHERE status = 'unread' OR status IS NULL")->fetchColumn();

    // Projets
    $totalProjects = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    $visibleProjects = $pdo->query("SELECT COUNT(*) FROM projects WHERE is_visible = 1")->fetchColumn();

    // Chatbot (24h)
    $chatbot24h = $pdo->prepare("SELECT COUNT(*) FROM chatbot_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $chatbot24h->execute();
    $chatbot24h = $chatbot24h->fetchColumn();

    // Graphique visites 30 derniers jours
    $chart = $pdo->query("
        SELECT visited_at AS date, COUNT(*) AS count
        FROM visitors
        WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY visited_at
        ORDER BY visited_at ASC
    ")->fetchAll();

    // Messages récents (5 derniers)
    $recentMessages = $pdo->query("
        SELECT id, name, email, subject, status, created_at
        FROM messages
        ORDER BY created_at DESC
        LIMIT 5
    ")->fetchAll();

    json_response([
        'kpis' => [
            'visitors_today' => (int)$todayVisitors,
            'visitors_week'  => (int)$weekVisitors,
            'visitors_total' => (int)$totalVisitors,
            'messages_unread' => (int)$unreadMessages,
            'messages_total'  => (int)$totalMessages,
            'projects_total'  => (int)$totalProjects,
            'projects_visible' => (int)$visibleProjects,
            'chatbot_24h'     => (int)$chatbot24h,
        ],
        'visits_chart' => $chart,
        'recent_messages' => $recentMessages,
    ]);

} catch (PDOException $e) {
    error_log("SDS Admin Overview Error: " . $e->getMessage());
    json_response(['error' => 'Erreur serveur.'], 500);
}
