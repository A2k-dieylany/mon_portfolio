<?php
/**
 * SDS Admin API — Projects CRUD
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
    if ($method === 'GET' && !isset($_GET['id'])) {
        $stmt = $pdo->query("SELECT id, title_fr, category_fr, main_image, is_visible, sort_order FROM projects ORDER BY sort_order ASC, id DESC");
        json_response(['projects' => $stmt->fetchAll()]);
    }

    // ===== GET SINGLE =====
    if ($method === 'GET' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        $project = $stmt->fetch();
        if (!$project) json_response(['error' => 'Projet introuvable.'], 404);

        $imgStmt = $pdo->prepare("SELECT * FROM project_images WHERE project_id = ? ORDER BY sort_order ASC, id ASC");
        $imgStmt->execute([$id]);
        $project['gallery'] = $imgStmt->fetchAll();

        json_response($project);
    }

    // ===== POST (Create) =====
    if ($method === 'POST') {
        $data = get_json_body();
        require_fields($data, ['title_fr']);

        $stmt = $pdo->prepare("INSERT INTO projects (title_fr, title_en, title_ar, desc_fr, desc_en, desc_ar, category_fr, category_en, category_ar, client_name, project_date, live_url, github_url, main_image, tags, sort_order, is_visible) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            sanitize($data['title_fr']),
            sanitize($data['title_en'] ?? $data['title_fr']),
            sanitize($data['title_ar'] ?? $data['title_fr']),
            sanitize($data['desc_fr'] ?? ''),
            sanitize($data['desc_en'] ?? $data['desc_fr'] ?? ''),
            sanitize($data['desc_ar'] ?? $data['desc_fr'] ?? ''),
            sanitize($data['category_fr'] ?? ''),
            sanitize($data['category_en'] ?? $data['category_fr'] ?? ''),
            sanitize($data['category_ar'] ?? $data['category_fr'] ?? ''),
            sanitize($data['client_name'] ?? ''),
            !empty($data['project_date']) ? $data['project_date'] : null,
            sanitize($data['live_url'] ?? ''),
            sanitize($data['github_url'] ?? ''),
            sanitize($data['main_image'] ?? ''),
            sanitize($data['tags'] ?? ''),
            (int)($data['sort_order'] ?? 0),
            isset($data['is_visible']) ? (int)$data['is_visible'] : 1
        ]);
        
        $projectId = $pdo->lastInsertId();

        // Gérer la galerie
        if (isset($data['gallery']) && is_array($data['gallery'])) {
            $galStmt = $pdo->prepare("INSERT INTO project_images (project_id, image_url, sort_order) VALUES (?, ?, ?)");
            foreach ($data['gallery'] as $index => $url) {
                if (!empty($url)) {
                    $galStmt->execute([$projectId, sanitize($url), $index]);
                }
            }
        }

        json_response(['success' => true, 'message' => 'Projet ajouté.', 'id' => $projectId]);
    }

    // ===== PUT (Update) =====
    if ($method === 'PUT') {
        $data = get_json_body();

        if (isset($data['reorder']) && is_array($data['items'])) {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE projects SET sort_order = ? WHERE id = ?");
            foreach ($data['items'] as $item) {
                $stmt->execute([(int)$item['sort_order'], (int)$item['id']]);
            }
            $pdo->commit();
            json_response(['success' => true, 'message' => 'Ordre mis à jour.']);
        }

        require_fields($data, ['id']);
        
        $sets = [];
        $params = [];
        $allowedFields = ['title_fr', 'title_en', 'title_ar', 'desc_fr', 'desc_en', 'desc_ar', 'category_fr', 'category_en', 'category_ar', 'client_name', 'project_date', 'live_url', 'github_url', 'main_image', 'tags', 'sort_order', 'is_visible'];
        
        foreach ($allowedFields as $f) {
            if (isset($data[$f])) {
                $sets[] = "$f = ?";
                $params[] = ($f === 'sort_order' || $f === 'is_visible') ? (int)$data[$f] : sanitize($data[$f]);
            }
        }

        if (empty($sets) && !isset($data['gallery'])) json_response(['error' => 'Rien à modifier.'], 400);

        $pdo->beginTransaction();

        if (!empty($sets)) {
            $params[] = (int)$data['id'];
            $stmt = $pdo->prepare("UPDATE projects SET " . implode(', ', $sets) . " WHERE id = ?");
            $stmt->execute($params);
        }

        if (isset($data['gallery']) && is_array($data['gallery'])) {
            // Effacer l'ancienne galerie
            $delStmt = $pdo->prepare("DELETE FROM project_images WHERE project_id = ?");
            $delStmt->execute([(int)$data['id']]);

            // Insérer la nouvelle
            $galStmt = $pdo->prepare("INSERT INTO project_images (project_id, image_url, sort_order) VALUES (?, ?, ?)");
            foreach ($data['gallery'] as $index => $url) {
                if (!empty($url)) {
                    $galStmt->execute([(int)$data['id'], sanitize($url), $index]);
                }
            }
        }

        $pdo->commit();
        json_response(['success' => true, 'message' => 'Projet mis à jour.']);
    }

    // ===== DELETE =====
    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) json_response(['error' => 'ID requis.'], 400);

        // Pas besoin de supprimer manuellement dans project_images grâce à ON DELETE CASCADE
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$id]);

        json_response(['success' => true, 'message' => 'Projet supprimé.']);
    }

    json_response(['error' => 'Méthode non supportée.'], 405);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("SDS Admin Projects Error: " . $e->getMessage());
    json_response(['error' => 'Erreur serveur.'], 500);
}
