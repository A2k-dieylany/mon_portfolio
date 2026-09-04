<?php
/**
 * SDS Admin API — Chatbot Logs
 */
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

require_auth();
security_headers();

$pdo = getDB();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get stats
    $stats = [
        'total_conversations' => 0,
        'today_conversations' => 0,
        'languages' => []
    ];
    
    $stats['total_conversations'] = $pdo->query("SELECT COUNT(DISTINCT session_id) FROM chatbot_logs")->fetchColumn();
    $stats['today_conversations'] = $pdo->query("SELECT COUNT(DISTINCT session_id) FROM chatbot_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    
    $langStats = $pdo->query("SELECT language, COUNT(*) as cnt FROM chatbot_logs GROUP BY language")->fetchAll(PDO::FETCH_ASSOC);
    $stats['languages'] = $langStats;

    // Get logs with pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    // Group by session_id to show conversations
    $stmt = $pdo->prepare("
        SELECT session_id, MIN(created_at) as start_time, MAX(created_at) as last_activity, COUNT(*) as msg_count,
        (SELECT language FROM chatbot_logs c2 WHERE c2.session_id = chatbot_logs.session_id LIMIT 1) as lang
        FROM chatbot_logs
        GROUP BY session_id
        ORDER BY last_activity DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get messages for a specific session if requested
    $session_id = $_GET['session_id'] ?? null;
    $messages = [];
    if ($session_id) {
        $msgStmt = $pdo->prepare("SELECT * FROM chatbot_logs WHERE session_id = ? ORDER BY created_at ASC");
        $msgStmt->execute([$session_id]);
        $messages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'stats' => $stats,
        'conversations' => $conversations,
        'messages' => $messages
    ]);
    exit;
}

if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!empty($data['session_id'])) {
        $stmt = $pdo->prepare("DELETE FROM chatbot_logs WHERE session_id = ?");
        $stmt->execute([$data['session_id']]);
        echo json_encode(['success' => true]);
        exit;
    }
    
    if (!empty($data['clear_all'])) {
        $pdo->query("TRUNCATE TABLE chatbot_logs");
        echo json_encode(['success' => true]);
        exit;
    }
    
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

http_response_code(405);
echo json_encode(['error'=>'Méthode non autorisée']);
