<?php
session_start();
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['error'=>'Non autorisé']); exit; }

require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// ===== GET — Récupérer tous les paramètres =====
if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM site_settings ORDER BY category ASC, id ASC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// ===== PUT — Mettre à jour un paramètre =====
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['setting_key']) || !isset($data['setting_value'])) {
        http_response_code(400);
        echo json_encode(['error'=>'Clé et valeur requises']);
        exit;
    }
    $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
    $stmt->execute([$data['setting_value'], $data['setting_key']]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success'=>true, 'message'=>'Paramètre mis à jour']);
    } else {
        echo json_encode(['success'=>false, 'message'=>'Aucune modification']);
    }
    exit;
}

// ===== POST — Ajouter un nouveau paramètre =====
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['setting_key']) || !isset($data['setting_value'])) {
        http_response_code(400);
        echo json_encode(['error'=>'Clé et valeur requises']);
        exit;
    }
    $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value, setting_type, category, label) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute([
        $data['setting_key'],
        $data['setting_value'],
        $data['setting_type'] ?? 'text',
        $data['category'] ?? 'general',
        $data['label'] ?? $data['setting_key']
    ]);
    echo json_encode(['success'=>true, 'message'=>'Paramètre ajouté']);
    exit;
}

// ===== DELETE — Supprimer un paramètre =====
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['setting_key'])) {
        http_response_code(400);
        echo json_encode(['error'=>'Clé requise']);
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM site_settings WHERE setting_key = ?");
    $stmt->execute([$data['setting_key']]);
    echo json_encode(['success'=>true]);
    exit;
}

http_response_code(405);
echo json_encode(['error'=>'Méthode non autorisée']);
