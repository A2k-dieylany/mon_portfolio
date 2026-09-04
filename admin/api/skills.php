<?php
/**
 * SDS Admin API — Skills CRUD
 * GET              → liste les compétences (triées par group, puis sort_order)
 * POST             → ajouter une compétence
 * PUT              → modifier une compétence ou son ordre
 * DELETE ?id=X     → supprimer
 */
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
        $stmt = $pdo->query("SELECT * FROM skills ORDER BY sort_order ASC, id DESC");
        json_response(['skills' => $stmt->fetchAll()]);
    }

    // ===== POST (Create) =====
    if ($method === 'POST') {
        $data = get_json_body();
        require_fields($data, ['group_name_fr', 'skill_name', 'percentage']);

        $stmt = $pdo->prepare("INSERT INTO skills (group_name_fr, group_name_en, group_name_ar, group_icon, skill_name, percentage, sort_order, is_visible) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            sanitize($data['group_name_fr']),
            sanitize($data['group_name_en'] ?? $data['group_name_fr']),
            sanitize($data['group_name_ar'] ?? $data['group_name_fr']),
            sanitize($data['group_icon'] ?? ''),
            sanitize($data['skill_name']),
            (int)$data['percentage'],
            (int)($data['sort_order'] ?? 0),
            isset($data['is_visible']) ? (int)$data['is_visible'] : 1
        ]);

        json_response(['success' => true, 'message' => 'Compétence ajoutée.']);
    }

    // ===== PUT (Update) =====
    if ($method === 'PUT') {
        $data = get_json_body();

        // Réorganisation (Drag & Drop)
        if (isset($data['reorder']) && is_array($data['items'])) {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE skills SET sort_order = ? WHERE id = ?");
            foreach ($data['items'] as $item) {
                $stmt->execute([(int)$item['sort_order'], (int)$item['id']]);
            }
            $pdo->commit();
            json_response(['success' => true, 'message' => 'Ordre mis à jour.']);
        }

        require_fields($data, ['id']);
        
        $sets = [];
        $params = [];
        $allowedFields = ['group_name_fr', 'group_name_en', 'group_name_ar', 'group_icon', 'skill_name', 'percentage', 'sort_order', 'is_visible'];
        
        foreach ($allowedFields as $f) {
            if (isset($data[$f])) {
                $sets[] = "$f = ?";
                $params[] = $f === 'percentage' || $f === 'sort_order' || $f === 'is_visible' ? (int)$data[$f] : sanitize($data[$f]);
            }
        }

        if (empty($sets)) json_response(['error' => 'Rien à modifier.'], 400);

        $params[] = (int)$data['id'];
        $stmt = $pdo->prepare("UPDATE skills SET " . implode(', ', $sets) . " WHERE id = ?");
        $stmt->execute($params);

        json_response(['success' => true, 'message' => 'Compétence mise à jour.']);
    }

    // ===== DELETE =====
    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) json_response(['error' => 'ID requis.'], 400);

        $stmt = $pdo->prepare("DELETE FROM skills WHERE id = ?");
        $stmt->execute([$id]);

        json_response(['success' => true, 'message' => 'Compétence supprimée.']);
    }

    json_response(['error' => 'Méthode non supportée.'], 405);

} catch (PDOException $e) {
    error_log("SDS Admin Skills Error: " . $e->getMessage());
    json_response(['error' => 'Erreur serveur.'], 500);
}
