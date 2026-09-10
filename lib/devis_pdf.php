<?php
/**
 * Génération du devis PDF aux couleurs de SEN DIGITAL SOLUTION.
 *
 * S'appuie sur FPDF (lib/fpdf.php), qui travaille en ISO-8859-1 : tout texte
 * passe par pdf_txt() avant d'être écrit, sinon les accents ressortent en
 * caractères parasites.
 */

require_once __DIR__ . '/fpdf.php';

/** Convertit une chaîne UTF-8 pour FPDF, qui n'accepte que le Latin-1. */
function pdf_txt(string $s): string
{
    $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s);
    // Les emojis n'ont pas d'équivalent Latin-1 : on les retire proprement.
    return $converted === false ? preg_replace('/[^\x20-\x7E]/', '', $s) : $converted;
}

/** Formate un montant à la sénégalaise : 150 000 FCFA. */
function pdf_money(float $amount): string
{
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

/**
 * Construit le devis et renvoie le PDF sous forme de chaîne binaire.
 *
 * @param array $q Ligne de crm_quotes (reference, service, amount, client_*…)
 */
function sds_build_devis(array $q): string
{
    $dark   = [10, 10, 15];
    $accent = [108, 99, 255];
    $gold   = [245, 166, 35];
    $grey   = [110, 110, 130];
    $line   = [225, 225, 235];

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetAutoPageBreak(true, 18);
    $pdf->AddPage();
    $pdf->SetMargins(18, 18, 18);

    // ---------------------------------------------------------- en-tête
    $pdf->SetFillColor(...$dark);
    $pdf->Rect(0, 0, 210, 34, 'F');
    $pdf->SetY(10);
    $pdf->SetX(18);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 19);
    $pdf->Cell(120, 8, pdf_txt('SEN DIGITAL SOLUTION'), 0, 1, 'L');
    $pdf->SetX(18);
    $pdf->SetTextColor(...$gold);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(120, 5, pdf_txt('Développement web · Automatisation · IA — Dakar'), 0, 1, 'L');

    // Référence, à droite du bandeau
    $pdf->SetY(12);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->Cell(174, 6, pdf_txt('DEVIS'), 0, 1, 'R');
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(200, 200, 215);
    $pdf->Cell(174, 5, pdf_txt($q['reference']), 0, 1, 'R');

    // ------------------------------------------------------ coordonnées
    $pdf->SetY(44);
    $pdf->SetTextColor(...$accent);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(87, 6, pdf_txt('CLIENT'), 0, 0, 'L');
    $pdf->Cell(87, 6, pdf_txt('PRESTATAIRE'), 0, 1, 'L');

    $client = array_filter([
        $q['client_name'] ?: 'Client',
        $q['client_company'] ?: null,
        $q['client_phone'] ?: null,
        $q['client_email'] ?: null,
    ]);
    $agency = [
        'SEN DIGITAL SOLUTION',
        'Dieylany Khouma',
        '+221 78 015 25 22',
        'sendigitalsolution@gmail.com',
    ];

    $pdf->SetTextColor(40, 40, 50);
    $pdf->SetFont('Helvetica', '', 9.5);
    $rows = max(count($client), count($agency));
    for ($i = 0; $i < $rows; $i++) {
        $pdf->Cell(87, 5.4, pdf_txt($client[$i] ?? ''), 0, 0, 'L');
        $pdf->Cell(87, 5.4, pdf_txt($agency[$i] ?? ''), 0, 1, 'L');
    }

    $pdf->Ln(4);
    $pdf->SetTextColor(...$grey);
    $pdf->SetFont('Helvetica', '', 8.5);
    $pdf->Cell(
        174,
        5,
        pdf_txt(sprintf(
            'Établi le %s · Valable jusqu\'au %s',
            date('d/m/Y', strtotime($q['created_at'])),
            date('d/m/Y', strtotime($q['valid_until']))
        )),
        0,
        1,
        'L'
    );
    $pdf->Ln(3);

    // -------------------------------------------------------- prestation
    $amount  = (float) $q['amount'];
    $deposit = round($amount / 2);

    $pdf->SetFillColor(...$accent);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(114, 9, pdf_txt('  Prestation'), 0, 0, 'L', true);
    $pdf->Cell(20, 9, pdf_txt('Qté'), 0, 0, 'C', true);
    $pdf->Cell(40, 9, pdf_txt('Montant  '), 0, 1, 'R', true);

    $pdf->SetTextColor(40, 40, 50);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetDrawColor(...$line);
    $pdf->Cell(114, 11, pdf_txt('  ' . $q['service']), 'B', 0, 'L');
    $pdf->Cell(20, 11, '1', 'B', 0, 'C');
    $pdf->Cell(40, 11, pdf_txt(pdf_money($amount) . '  '), 'B', 1, 'R');

    // Ce qui est compris : c'est ce qui fait accepter un devis.
    $pdf->SetFont('Helvetica', 'I', 8.5);
    $pdf->SetTextColor(6, 140, 100);
    foreach ([
        'Hébergement et nom de domaine offerts la première année',
        'Trois mois de maintenance incluse après la livraison',
        'Formation à la prise en main de l\'outil',
    ] as $included) {
        $pdf->Cell(174, 5.2, pdf_txt('   + ' . $included), 0, 1, 'L');
    }

    // ------------------------------------------------------------- totaux
    $pdf->Ln(4);
    $pdf->SetTextColor(40, 40, 50);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(114, 7, '', 0, 0);
    $pdf->Cell(20, 7, pdf_txt('Total'), 0, 0, 'L');
    $pdf->Cell(40, 7, pdf_txt(pdf_money($amount) . '  '), 0, 1, 'R');

    $pdf->SetTextColor(200, 60, 60);
    $pdf->Cell(114, 7, '', 0, 0);
    $pdf->Cell(20, 7, pdf_txt('Acompte'), 0, 0, 'L');
    $pdf->Cell(40, 7, pdf_txt(pdf_money($deposit) . '  '), 0, 1, 'R');

    $pdf->SetFillColor(...$dark);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(114, 11, '', 0, 0);
    $pdf->Cell(20, 11, pdf_txt(' NET'), 0, 0, 'L', true);
    $pdf->Cell(40, 11, pdf_txt(pdf_money($amount) . '  '), 0, 1, 'R', true);

    // ------------------------------------------------------- conditions
    $pdf->Ln(8);
    $pdf->SetTextColor(...$accent);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(174, 6, pdf_txt('CONDITIONS'), 0, 1, 'L');

    $pdf->SetTextColor(70, 70, 85);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->MultiCell(174, 5.2, pdf_txt(
        "Acompte de 50 % à la commande, solde à la livraison.\n"
        . "Paiement accepté par Wave, Orange Money ou virement bancaire.\n"
        . "Le délai de réalisation court à compter de la réception de l'acompte "
        . "et de l'ensemble des contenus (textes, images, accès).\n"
        . "Ce devis ne constitue pas une facture."
    ), 0, 'L');

    // ---------------------------------------------------------- pied de page
    $pdf->SetY(-26);
    $pdf->SetDrawColor(...$line);
    $pdf->Line(18, $pdf->GetY(), 192, $pdf->GetY());
    $pdf->Ln(3);
    $pdf->SetTextColor(...$grey);
    $pdf->SetFont('Helvetica', '', 8);
    $pdf->Cell(174, 4.5, pdf_txt('SEN DIGITAL SOLUTION — Dakar, Sénégal — dieylany.dev'), 0, 1, 'C');
    $pdf->Cell(174, 4.5, pdf_txt('Devis établi automatiquement par MAX, l\'assistante de l\'agence.'), 0, 1, 'C');

    return $pdf->Output('S');
}
