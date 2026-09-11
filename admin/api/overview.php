<?php
/**
 * SDS Admin API — Overview KPIs
 */
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

    // Pipeline commercial : la page d'accueil de l'admin d'une agence doit
    // montrer ce qui rapporte, pas seulement les visites. Absent si le CRM
    // n'est pas installé, pour ne pas faire échouer toute la page.
    $crm = null;
    if ($pdo->query("SHOW TABLES LIKE 'crm_deals'")->fetchColumn()) {
        $s = $pdo->query(
            "SELECT
                SUM(stage NOT IN ('gagne','perdu'))                                        AS open_count,
                COALESCE(SUM(CASE WHEN stage NOT IN ('gagne','perdu') THEN amount END), 0) AS open_amount,
                SUM(stage = 'gagne')                                                        AS won_count,
                COALESCE(SUM(CASE WHEN stage = 'gagne' THEN amount END), 0)                 AS won_amount,
                SUM(stage NOT IN ('gagne','perdu') AND next_action_at < CURDATE())         AS overdue,
                SUM(stage = 'nouveau')                                                      AS new_count
             FROM crm_deals"
        )->fetch();

        // Les relances datées d'abord (les plus urgentes en tête), puis les
        // opportunités sans date, les plus récentes en premier.
        $next = $pdo->query(
            "SELECT d.id, d.title, d.stage, d.amount, d.next_action, d.next_action_at,
                    c.full_name AS contact_name, c.company AS contact_company
               FROM crm_deals d
               LEFT JOIN crm_contacts c ON c.id = d.contact_id
              WHERE d.stage NOT IN ('gagne','perdu')
              ORDER BY (d.next_action_at IS NULL), d.next_action_at ASC, d.created_at DESC
              LIMIT 5"
        )->fetchAll();

        $crm = [
            'open_count'  => (int) $s['open_count'],
            'open_amount' => (float) $s['open_amount'],
            'won_count'   => (int) $s['won_count'],
            'won_amount'  => (float) $s['won_amount'],
            'overdue'     => (int) $s['overdue'],
            'new_count'   => (int) $s['new_count'],
            'next'        => $next,
        ];
    }

    json_response([
        'crm' => $crm,
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
