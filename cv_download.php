<?php
// cv_download.php — Convertit cv.jpg en PDF et sert le téléchargement
$imagePath = __DIR__ . '/img/projects/cv.jpg';

if (!file_exists($imagePath)) {
    http_response_code(404);
    echo "CV non trouvé.";
    exit;
}

// Récupérer les dimensions de l'image
$imageInfo = getimagesize($imagePath);
if (!$imageInfo) {
    http_response_code(500);
    echo "Image invalide.";
    exit;
}

$imgWidth  = $imageInfo[0];
$imgHeight = $imageInfo[1];
$imageData = file_get_contents($imagePath);

// Dimensions de la page PDF en points (A4 = 595.28 x 841.89)
// On adapte la page à l'image en gardant le ratio
$maxW = 595.28;
$maxH = 841.89;
$ratio = min($maxW / $imgWidth, $maxH / $imgHeight);
$pdfW = round($imgWidth * $ratio, 2);
$pdfH = round($imgHeight * $ratio, 2);

// Centrer l'image sur la page A4
$offsetX = round(($maxW - $pdfW) / 2, 2);
$offsetY = round(($maxH - $pdfH) / 2, 2);

// Construction du PDF brut (pas besoin de librairie externe)
$imgLength = strlen($imageData);

$pdf  = "%PDF-1.4\n";

// Objet 1 : Catalogue
$obj1Offset = strlen($pdf);
$pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

// Objet 2 : Pages
$obj2Offset = strlen($pdf);
$pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

// Objet 3 : Page
$obj3Offset = strlen($pdf);
$pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $maxW $maxH] /Contents 4 0 R /Resources << /XObject << /Img 5 0 R >> >> >>\nendobj\n";

// Objet 4 : Contenu de la page (dessiner l'image)
$stream = "q\n{$pdfW} 0 0 {$pdfH} {$offsetX} {$offsetY} cm\n/Img Do\nQ\n";
$streamLen = strlen($stream);
$obj4Offset = strlen($pdf);
$pdf .= "4 0 obj\n<< /Length {$streamLen} >>\nstream\n{$stream}endstream\nendobj\n";

// Objet 5 : Image XObject (JPEG)
$obj5Offset = strlen($pdf);
$pdf .= "5 0 obj\n<< /Type /XObject /Subtype /Image /Width {$imgWidth} /Height {$imgHeight} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length {$imgLength} >>\nstream\n";
$pdf .= $imageData;
$pdf .= "\nendstream\nendobj\n";

// Table de références croisées
$xrefOffset = strlen($pdf);
$pdf .= "xref\n0 6\n";
$pdf .= "0000000000 65535 f \n";
$pdf .= sprintf("%010d 00000 n \n", $obj1Offset);
$pdf .= sprintf("%010d 00000 n \n", $obj2Offset);
$pdf .= sprintf("%010d 00000 n \n", $obj3Offset);
$pdf .= sprintf("%010d 00000 n \n", $obj4Offset);
$pdf .= sprintf("%010d 00000 n \n", $obj5Offset);

// Trailer
$pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\n";
$pdf .= "startxref\n{$xrefOffset}\n%%EOF";

// Servir le PDF en téléchargement
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="CV_Dieylany_SDS.pdf"');
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: no-cache, must-revalidate');
echo $pdf;
exit;
?>
