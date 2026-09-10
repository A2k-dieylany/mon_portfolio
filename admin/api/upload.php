<?php
/**
 * SDS Admin API — Upload Images
 *
 * Le disque de la fonction Vercel n'est pas persistant : les images sont
 * envoyées à Vercel Blob (stockage objet) via son API REST au lieu d'être
 * écrites sur le disque local.
 */
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../../config.php';

require_auth();

// L'upload nécessite un header différent (multipart/form-data), on ne peut pas utiliser get_json_body()
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non supportée.']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    // « Erreur serveur » ne dit rien. On renvoie la cause réelle : le code
    // d'erreur PHP, ou l'état des limites quand le fichier n'arrive même pas.
    $codes = [
        UPLOAD_ERR_INI_SIZE   => "Le fichier dépasse la limite du serveur (upload_max_filesize).",
        UPLOAD_ERR_FORM_SIZE  => "Le fichier dépasse la limite du formulaire.",
        UPLOAD_ERR_PARTIAL    => "Le fichier n'est arrivé que partiellement.",
        UPLOAD_ERR_NO_FILE    => "Aucun fichier n'a été reçu.",
        UPLOAD_ERR_NO_TMP_DIR => "Le dossier temporaire du serveur est introuvable.",
        UPLOAD_ERR_CANT_WRITE => "Le serveur n'a pas pu écrire le fichier temporaire.",
        UPLOAD_ERR_EXTENSION  => "Une extension PHP a interrompu l'envoi.",
    ];

    $code = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
    $diag = [
        'error'  => $codes[$code] ?? "Erreur d'envoi inconnue (code $code).",
        'code'   => $code,
        // Quand rien n'arrive, ces valeurs disent si c'est une limite de
        // taille ou si le runtime n'analyse pas les envois multipart.
        'recu'   => [
            'content_type'   => $_SERVER['CONTENT_TYPE'] ?? '(absent)',
            'content_length' => $_SERVER['CONTENT_LENGTH'] ?? '(absent)',
            'nb_fichiers'    => count($_FILES),
            'champs_postes'  => array_keys($_POST),
        ],
        'limites' => [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size'       => ini_get('post_max_size'),
            'file_uploads'        => ini_get('file_uploads') ? 'oui' : 'non',
            'upload_tmp_dir'      => ini_get('upload_tmp_dir') ?: sys_get_temp_dir(),
        ],
    ];
    error_log('SDS Upload — ' . json_encode($diag, JSON_UNESCAPED_UNICODE));

    http_response_code(400);
    echo json_encode($diag, JSON_UNESCAPED_UNICODE);
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

// Forcer l'extension en fonction du type MIME réel (sécurité contre usurpation)
$mimeToExt = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif'
];
$ext = $mimeToExt[$mimeType] ?? 'jpg';
$filename = uniqid('proj_') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$pathname = 'projects/' . $filename;

if (BLOB_READ_WRITE_TOKEN === '') {
    http_response_code(500);
    echo json_encode(['error' => 'Stockage non configuré (BLOB_READ_WRITE_TOKEN manquant).']);
    exit;
}

$fileData = file_get_contents($file['tmp_name']);
// rawurlencode puis restauration des "/" : on veut encoder le nom de fichier
// mais garder le séparateur de dossier "projects/" intact dans l'URL.
$encodedPathname = str_replace('%2F', '/', rawurlencode($pathname));

$ch = curl_init("https://blob.vercel-storage.com/?pathname={$encodedPathname}");
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST  => 'PUT',
    CURLOPT_POSTFIELDS     => $fileData,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'access: public',
        'authorization: Bearer ' . BLOB_READ_WRITE_TOKEN,
        'x-api-version: 10',
        'x-content-type: ' . $mimeType,
    ],
    CURLOPT_TIMEOUT => 30,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false || $httpCode !== 200) {
    error_log("SDS Upload — Vercel Blob error ({$httpCode}): " . ($curlError ?: $response));
    http_response_code(500);
    echo json_encode([
        'error'   => "Le stockage a refusé le fichier (HTTP $httpCode).",
        'detail'  => $curlError ?: (json_decode($response, true) ?? $response),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$blobResult = json_decode($response, true);
if (!isset($blobResult['url'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Réponse inattendue du service de stockage.']);
    exit;
}

echo json_encode(['success' => true, 'url' => $blobResult['url']]);
