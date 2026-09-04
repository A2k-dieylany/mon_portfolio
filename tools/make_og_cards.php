<?php
/**
 * Génère les cartes Open Graph 1200x630 des pages services.
 *
 * Usage : php tools/make_og_cards.php
 * Source : tools/og_services.json — une ligne JSON par service
 *          {"slug":…, "title":…, "headline":…, "badges":[prix, délai]}
 * Sortie : img/og/<slug>.jpg (+ default.jpg pour l'accueil)
 *
 * Le script n'est pas déployé (voir .vercelignore) : les images générées le sont.
 */
$fontBold    = 'C:/Windows/Fonts/arialbd.ttf';
$fontRegular = 'C:/Windows/Fonts/arial.ttf';
$outDir      = dirname(__DIR__) . '/img/og';

$services = [];
foreach (file(__DIR__ . '/og_services.json', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $decoded = json_decode(trim($line), true);
    if (is_array($decoded)) { $services[] = $decoded; }
}
$services[] = ['slug' => 'default', 'title' => 'SEN DIGITAL SOLUTION',
               'headline' => 'Sites web, automatisation et agents IA à Dakar', 'badges' => []];

function wrapText(string $text, string $font, int $size, int $maxWidth): array {
    $words = preg_split('/\s+/u', $text);
    $lines = [];
    $current = '';
    foreach ($words as $w) {
        $try = $current === '' ? $w : $current . ' ' . $w;
        $box = imagettfbbox($size, 0, $font, $try);
        if ($box[2] - $box[0] > $maxWidth && $current !== '') {
            $lines[] = $current;
            $current = $w;
        } else {
            $current = $try;
        }
    }
    if ($current !== '') $lines[] = $current;
    return $lines;
}

foreach ($services as $s) {
    $W = 1200; $H = 630;
    $im = imagecreatetruecolor($W, $H);

    // Fond : dégradé vertical sombre + halo doré radial très diffus, calculé
    // pixel par pixel — empiler des ellipses semi-transparentes sature et
    // produit une tache opaque au lieu d'un dégradé.
    $gx = 1150; $gy = -40; $gr = 620;
    for ($py = 0; $py < $H; $py++) {
        $t = $py / $H;
        $br = 10 + 8 * $t; $bg = 10 + 8 * $t; $bb = 15 + 14 * $t;
        for ($px = 0; $px < $W; $px++) {
            $d = sqrt(($px - $gx) ** 2 + ($py - $gy) ** 2);
            $g = $d >= $gr ? 0.0 : (1 - $d / $gr) ** 2.4 * 0.30;
            imagesetpixel($im, $px, $py, imagecolorallocate(
                $im,
                (int) min(255, $br + (245 - $br) * $g),
                (int) min(255, $bg + (166 - $bg) * $g),
                (int) min(255, $bb + (35  - $bb) * $g)
            ));
        }
    }

    $gold  = imagecolorallocate($im, 245, 166, 35);
    $white = imagecolorallocate($im, 240, 240, 250);
    $muted = imagecolorallocate($im, 140, 140, 175);

    // Barre d'accent à gauche
    imagefilledrectangle($im, 0, 0, 10, $H, $gold);

    $x = 80;
    // Eyebrow : nom du service
    imagettftext($im, 26, 0, $x, 130, $gold, $fontBold, mb_strtoupper($s['title'], 'UTF-8'));

    // Titre : l'accroche client
    $lines = wrapText($s['headline'], $fontBold, 58, $W - 2 * $x - 60);
    $y = 230;
    foreach (array_slice($lines, 0, 3) as $line) {
        imagettftext($im, 58, 0, $x, $y, $white, $fontBold, $line);
        $y += 78;
    }

    // Badges : prix et délai
    $badges = [];
    foreach ($s['badges'] ?? [] as $b) {
        // On retire l'emoji de tête, qui ne se rend pas en TTF.
        $clean = trim(preg_replace('/^[^\p{L}\p{N}]+/u', '', $b));
        if ($clean !== '') {
            $badges[] = mb_strtoupper(mb_substr($clean, 0, 1), 'UTF-8') . mb_substr($clean, 1);
        }
    }
    if ($badges) {
        $line = implode('   ·   ', $badges);
        // On réduit la taille tant que la ligne dépasse la zone de texte.
        $size = 27;
        while ($size > 17) {
            $box = imagettfbbox($size, 0, $fontRegular, $line);
            if ($box[2] - $box[0] <= $W - 2 * $x) break;
            $size -= 1;
        }
        imagettftext($im, $size, 0, $x, max($y + 40, 500), $muted, $fontRegular, $line);
    }

    // Signature : on mesure « dieylany.dev » pour placer la suite sans chevauchement.
    imagettftext($im, 25, 0, $x, 580, $gold, $fontBold, 'dieylany.dev');
    $sig = imagettfbbox(25, 0, $fontBold, 'dieylany.dev');
    imagettftext($im, 25, 0, $x + ($sig[2] - $sig[0]) + 24, 580, $muted, $fontRegular, 'SEN DIGITAL SOLUTION');

    $path = $outDir . '/' . $s['slug'] . '.jpg';
    imagejpeg($im, $path, 86);
    imagedestroy($im);
    printf("%-26s %6d octets\n", basename($path), filesize($path));
}
