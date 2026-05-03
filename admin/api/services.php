<?php
/**
 * SDS Admin API — Services CRUD
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
    // ===== GET LIST =====
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT * FROM services ORDER BY sort_order ASC, id ASC");
        json_response(['services' => $stmt->fetchAll()]);
    }

    // ===== POST (Create) =====
    if ($method === 'POST') {
        $data = get_json_body();
        require_fields($data, ['title_fr', 'desc_fr', 'icon']);

        $stmt = $pdo->prepare("INSERT INTO services (icon, title_fr, title_en, title_ar, desc_fr, desc_en, desc_ar, tags, sort_order, is_visible) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            sanitize($data['icon']),
            sanitize($data['title_fr']),
            sanitize($data['title_en'] ?? $data['title_fr']),
            sanitize($data['title_ar'] ?? $data['title_fr']),
            sanitize($data['desc_fr']),
            sanitize($data['desc_en'] ?? $data['desc_fr']),
            sanitize($data['desc_ar'] ?? $data['desc_fr']),
            sanitize($data['tags'] ?? ''),
            (int)($data['sort_order'] ?? 0),
            isset($data['is_visible']) ? (int)$data['is_visible'] : 1
        ]);

        json_response(['success' => true, 'message' => 'Service ajouté.']);
    }

    // ===== PUT (Update) =====
    if ($method === 'PUT') {
        $data = get_json_body();
        require_fields($data, ['id']);
        
        $sets = [];
        $params = [];
        $allowedFields = ['icon', 'title_fr', 'title_en', 'title_ar', 'desc_fr', 'desc_en', 'desc_ar', 'tags', 'sort_order', 'is_visible'];
        
        foreach ($allowedFields as $f) {
            if (isset($data[$f])) {
                $sets[] = "$f = ?";
                $params[] = $f === 'sort_order' || $f === 'is_visible' ? (int)$data[$f] : sanitize($data[$f]);
            }
        }

        if (empty($sets)) json_response(['error' => 'Rien à modifier.'], 400);

        $params[] = (int)$data['id'];
        $stmt = $pdo->prepare("UPDATE services SET " . implode(', ', $sets) . " WHERE id = ?");
        $stmt->execute($params);

        json_response(['success' => true, 'message' => 'Service mis à jour.']);
    }

    // ===== DELETE =====
    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) json_response(['error' => 'ID requis.'], 400);

        $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
        $stmt->execute([$id]);

        json_response(['success' => true, 'message' => 'Service supprimé.']);
    }

    json_response(['error' => 'Méthode non supportée.'], 405);

} catch (PDOException $e) {
    error_log("SDS Admin Services Error: " . $e->getMessage());
    json_response(['error' => 'Erreur serveur.'], 500);
}
