<?php
/**
 * Téléchargement du CV.
 *
 * Sert en priorité le vrai PDF déposé dans docs/. À défaut seulement, il
 * retombe sur l'ancien procédé : une image de CV enveloppée dans un PDF.
 *
 * La différence n'est pas cosmétique. Un CV-image ne contient aucun texte :
 * les logiciels de recrutement qui analysent les candidatures n'y lisent
 * rien, pas même le nom. Un vrai PDF est lu, indexé et cherchable.
 */

$pdfPath   = __DIR__ . '/docs/CV_Dieylany_SDS.pdf';
$imagePath = __DIR__ . '/img/projects/cv.jpg';
$filename  = 'CV_Dieylany_SDS.pdf';

// zlib est activé par le dispatcher ; un PDF est déjà compressé.
if (ini_get('zlib.output_compression')) {
    @ini_set('zlib.output_compression', '0');
}

if (is_file($pdfPath)) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($pdfPath));
    header('Cache-Control: public, max-age=3600');
    readfile($pdfPath);
    exit;
}

// ---------------------------------------------------------- repli : image
if (!file_exists($imagePath)) {
    http_response_code(404);
    echo 'CV non trouvé.';
    exit;
}

$imageInfo = getimagesize($imagePath);
if (!$imageInfo) {
    http_response_code(500);
    echo 'Image invalide.';
    exit;
}

$imgWidth  = $imageInfo[0];
$imgHeight = $imageInfo[1];
$imageData = file_get_contents($imagePath);

// A4 en points, l'image centrée en conservant ses proportions.
$maxW    = 595.28;
$maxH    = 841.89;
$ratio   = min($maxW / $imgWidth, $maxH / $imgHeight);
$pdfW    = round($imgWidth * $ratio, 2);
$pdfH    = round($imgHeight * $ratio, 2);
$offsetX = round(($maxW - $pdfW) / 2, 2);
$offsetY = round(($maxH - $pdfH) / 2, 2);

$imgLength = strlen($imageData);
$objects   = [];

$objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
$objects[2] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
$objects[3] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $maxW $maxH] "
            . "/Resources << /XObject << /Im1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
$objects[4] = "4 0 obj\n<< /Type /XObject /Subtype /Image /Width $imgWidth /Height $imgHeight "
            . "/ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length $imgLength >>\n"
            . "stream\n" . $imageData . "\nendstream\nendobj\n";

$content = "q\n$pdfW 0 0 $pdfH $offsetX $offsetY cm\n/Im1 Do\nQ\n";
$objects[5] = "5 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n$content\nendstream\nendobj\n";

$pdf     = "%PDF-1.4\n";
$offsets = [];
foreach ($objects as $number => $body) {
    $offsets[$number] = strlen($pdf);
    $pdf .= $body;
}

$xrefPosition = strlen($pdf);
$pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
foreach ($objects as $number => $body) {
    $pdf .= sprintf("%010d 00000 n \n", $offsets[$number]);
}
$pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n"
      . "startxref\n$xrefPosition\n%%EOF";

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
