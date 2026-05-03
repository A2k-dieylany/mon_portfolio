<?php
/**
 * SDS Admin API — Upload Images
 */
session_start();
require_once __DIR__ . '/../includes/auth_check.php';

require_auth();

// L'upload nécessite un header différent (multipart/form-data), on ne peut pas utiliser get_json_body()
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non supportée.']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Aucun fichier uploadé ou erreur lors de l\'upload.']);
    exit;
}

$file = $_FILES['image'];
$maxSize = 5 * 1024 * 1024; // 5 MB

if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'Le fichier dépasse la taille maximale (5MB).']);
    exit;
}

$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedMimeTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Format de fichier non autorisé (JPEG, PNG, WEBP, GIF uniquement).']);
    exit;
}

// Nettoyer le nom du fichier et générer un nom unique
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('proj_') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

// Chemin de destination: sds/images/projects/
$uploadDir = __DIR__ . '/../../images/projects/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$destPath = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $destPath)) {
    // Renvoyer le chemin relatif pour la base de données
    $relativePath = 'images/projects/' . $filename;
    echo json_encode(['success' => true, 'url' => $relativePath]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de l\'écriture du fichier sur le serveur.']);
}
