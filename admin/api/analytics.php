<?php
session_start();
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['error'=>'Non autorisé']); exit; }

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();

$action = $_GET['action'] ?? 'summary';

// ===== Résumé global =====
if ($action === 'summary') {
    $today = date('Y-m-d');
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $monthStart = date('Y-m-01');

    $total     = $pdo->query("SELECT COUNT(*) FROM visitors")->fetchColumn();
    $todayC    = $pdo->prepare("SELECT COUNT(*) FROM visitors WHERE visited_at = ?");
    $todayC->execute([$today]);
    $todayCount = $todayC->fetchColumn();

    $weekC = $pdo->prepare("SELECT COUNT(*) FROM visitors WHERE visited_at >= ?");
    $weekC->execute([$weekStart]);
    $weekCount = $weekC->fetchColumn();

    $monthC = $pdo->prepare("SELECT COUNT(*) FROM visitors WHERE visited_at >= ?");
    $monthC->execute([$monthStart]);
    $monthCount = $monthC->fetchColumn();

    // Messages
    $msgTotal  = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
    $msgUnread = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();

    echo json_encode([
        'visitors_total'  => (int)$total,
        'visitors_today'  => (int)$todayCount,
        'visitors_week'   => (int)$weekCount,
        'visitors_month'  => (int)$monthCount,
        'messages_total'  => (int)$msgTotal,
        'messages_unread' => (int)$msgUnread,
        'projects_count'  => (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE is_visible=1")->fetchColumn(),
        'services_count'  => (int)$pdo->query("SELECT COUNT(*) FROM services WHERE is_visible=1")->fetchColumn()
    ]);
    exit;
}

// ===== Graphique des 30 derniers jours =====
if ($action === 'chart_30days') {
    $stmt = $pdo->query("
        SELECT visited_at AS day, COUNT(*) AS cnt
        FROM visitors
        WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY visited_at
        ORDER BY visited_at ASC
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Remplir les jours manquants avec 0
    $data = [];
    $labels = [];
    $start = new DateTime('-29 days');
    $end = new DateTime('tomorrow');
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end);

    $map = [];
    foreach ($rows as $r) $map[$r['day']] = (int)$r['cnt'];

    foreach ($period as $dt) {
        $d = $dt->format('Y-m-d');
        $labels[] = $dt->format('d/m');
        $data[] = $map[$d] ?? 0;
    }

    echo json_encode(['labels' => $labels, 'data' => $data]);
    exit;
}

// ===== Graphique par heure (aujourd'hui) =====
if ($action === 'chart_hours') {
    $stmt = $pdo->query("
        SELECT HOUR(created_at) AS h, COUNT(*) AS cnt
        FROM visitors
        WHERE visited_at = CURDATE()
        GROUP BY HOUR(created_at)
        ORDER BY h ASC
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $map = [];
    foreach ($rows as $r) $map[(int)$r['h']] = (int)$r['cnt'];

    $labels = [];
    $data = [];
    for ($i = 0; $i < 24; $i++) {
        $labels[] = str_pad($i, 2, '0', STR_PAD_LEFT) . 'h';
        $data[] = $map[$i] ?? 0;
    }

    echo json_encode(['labels' => $labels, 'data' => $data]);
    exit;
}

// ===== Top pages vues =====
if ($action === 'top_pages') {
    $stmt = $pdo->query("
        SELECT page, COUNT(*) AS cnt
        FROM page_views
        GROUP BY page
        ORDER BY cnt DESC
        LIMIT 10
    ");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// ===== Répartition devices =====
if ($action === 'devices') {
    $stmt = $pdo->query("
        SELECT device, COUNT(*) AS cnt
        FROM page_views
        GROUP BY device
        ORDER BY cnt DESC
    ");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// ===== Liste des dernières visites =====
if ($action === 'recent') {
    $stmt = $pdo->query("
        SELECT id, LEFT(ip_hash, 8) AS visitor_hash, country, visited_at, created_at
        FROM visitors
        ORDER BY created_at DESC
        LIMIT 50
    ");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

echo json_encode(['error' => 'Action inconnue']);
