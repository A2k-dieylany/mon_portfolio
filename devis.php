<?php
/**
 * Téléchargement d'un devis — URL publique : /devis/<token>
 *
 * Le jeton fait 32 caractères aléatoires : il n'est ni devinable, ni
 * énumérable, ce qui évite d'exposer les devis des autres clients sans
 * imposer de compte au prospect.
 */
require_once __DIR__ . '/admin/includes/db.php';

$token = $_GET['token'] ?? '';
$token = preg_replace('/[^a-f0-9]/', '', strtolower($token));

if (strlen($token) !== 32) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT * FROM crm_quotes WHERE token = ? LIMIT 1');
    $stmt->execute([$token]);
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Devis : ' . $e->getMessage());
    $quote = null;
}

if (!$quote) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

// Savoir si le prospect a ouvert son devis en dit long sur son intérêt.
try {
    $pdo->prepare('UPDATE crm_quotes SET downloaded_at = NOW() WHERE id = ? AND downloaded_at IS NULL')
        ->execute([$quote['id']]);
} catch (Throwable $e) {
    error_log('Devis (marquage) : ' . $e->getMessage());
}

require_once __DIR__ . '/lib/devis_pdf.php';
$pdf = sds_build_devis($quote);

// zlib est activé par le dispatcher ; un PDF est déjà compressé.
if (ini_get('zlib.output_compression')) {
    @ini_set('zlib.output_compression', '0');
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $quote['reference'] . '.pdf"');
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: private, no-store');
echo $pdf;
