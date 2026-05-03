<?php
/**
 * SDS Admin API — Messages CRUD
 * GET              → liste tous les messages
 * GET ?count_unread=1 → compte les non lus
 * GET ?id=X        → détail d'un message
 * PUT              → modifier statut/notes
 * DELETE ?id=X     → supprimer
 */
session_start();
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

require_auth();
security_headers();

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDB();

try {
    // ===== COUNT UNREAD =====
    if ($method === 'GET' && isset($_GET['count_unread'])) {
        $count = $pdo->query("SELECT COUNT(*) FROM messages WHERE status = 'unread' OR status IS NULL")->fetchColumn();
        json_response(['count' => (int)$count]);
    }

    // ===== GET SINGLE =====
    if ($method === 'GET' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
        $stmt->execute([(int)$_GET['id']]);
        $msg = $stmt->fetch();
        if (!$msg) json_response(['error' => 'Message introuvable.'], 404);
        json_response($msg);
    }

    // ===== GET LIST =====
    if ($method === 'GET') {
        $where = "1=1";
        $params = [];

        if (!empty($_GET['status'])) {
            $where .= " AND status = ?";
            $params[] = $_GET['status'];
        }
        if (!empty($_GET['search'])) {
            $where .= " AND (name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
            $s = '%' . $_GET['search'] . '%';
            $params = array_merge($params, [$s, $s, $s, $s]);
        }

        $stmt = $pdo->prepare("SELECT id, name, email, subject, status, created_at FROM messages WHERE $where ORDER BY created_at DESC LIMIT 100");
        $stmt->execute($params);
        json_response(['messages' => $stmt->fetchAll()]);
    }

    // ===== PUT (update) =====
    if ($method === 'PUT') {
        $data = get_json_body();
        require_fields($data, ['id']);

        $id = (int)$data['id'];
        $sets = [];
        $params = [];

        if (isset($data['status'])) {
            $allowed = ['unread', 'read', 'replied', 'archived'];
            if (!in_array($data['status'], $allowed)) json_response(['error' => 'Statut invalide.'], 400);
            $sets[] = "status = ?";
            $params[] = $data['status'];
            if ($data['status'] === 'replied') {
                $sets[] = "replied_at = NOW()";
            }
        }
        if (isset($data['notes'])) {
            $sets[] = "notes = ?";
            $params[] = sanitize($data['notes']);
        }

        if (empty($sets)) json_response(['error' => 'Rien à modifier.'], 400);

        $params[] = $id;
        $stmt = $pdo->prepare("UPDATE messages SET " . implode(', ', $sets) . " WHERE id = ?");
        $stmt->execute($params);

        json_response(['success' => true, 'message' => 'Message mis à jour.']);
    }

    // ===== DELETE =====
    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) json_response(['error' => 'ID requis.'], 400);

        $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
        $stmt->execute([$id]);

        json_response(['success' => true, 'message' => 'Message supprimé.']);
    }

    json_response(['error' => 'Méthode non supportée.'], 405);

} catch (PDOException $e) {
    error_log("SDS Admin Messages Error: " . $e->getMessage());
    json_response(['error' => 'Erreur serveur.'], 500);
}
