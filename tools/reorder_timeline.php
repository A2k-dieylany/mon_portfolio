<?php
/**
 * Applique l'ordre et les années de la frise dictés par Dieylany.
 * Usage : php tools/reorder_timeline.php [--apply]
 */
$apply = in_array('--apply', $argv, true);

$env = [];
foreach (file(dirname(__DIR__) . '/.env.local', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
    if (str_contains($l, '=')) { [$k, $v] = explode('=', $l, 2); $env[trim($k)] = trim($v); }
}
$pdo = new PDO(
    "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_NAME']};charset=utf8mb4",
    $env['DB_USER'], $env['DB_PASS'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// Ordre demandé, avec les années à corriger le cas échéant.
// null = on garde l'année déjà en base.
$plan = [
    ['id' => 1, 'years' => null],                                             // 2022 — Concours Général
    ['id' => 2, 'years' => null],                                             // 2023 — Baccalauréat
    ['id' => 3, 'years' => null],                                             // Licence 1 — IPD
    ['id' => 4, 'years' => ['2025', '2025', '2025']],                          // Fondation SDS
    ['id' => 5, 'years' => null],                                             // Dibiterie Ameth Boll
    ['id' => 7, 'years' => null],                                             // 2025-2026 — Licence 2 ISI
    ['id' => 6, 'years' => ['2026', '2026', '2026']],                         // WhatsApp Bot IA
    ['id' => 8, 'years' => ['2026', '2026', '2026']],                         // Spécialiste IA
];

$rows = [];
foreach ($pdo->query("SELECT id, sort_order, year_fr, year_en, year_ar, title_fr FROM timeline_items") as $r) {
    $rows[$r['id']] = $r;
}

$updates = [];
foreach ($plan as $rank => $step) {
    $row = $rows[$step['id']] ?? null;
    if (!$row) { continue; }

    $sets = [];
    $args = [];
    if ((int) $row['sort_order'] !== $rank) {
        $sets[] = 'sort_order = ?';
        $args[] = $rank;
    }
    if ($step['years'] !== null) {
        [$fr, $en, $ar] = $step['years'];
        if ($row['year_fr'] !== $fr || $row['year_en'] !== $en || $row['year_ar'] !== $ar) {
            array_push($sets, 'year_fr = ?', 'year_en = ?', 'year_ar = ?');
            array_push($args, $fr, $en, $ar);
        }
    }
    if ($sets) {
        $args[] = $step['id'];
        $updates[] = ['sql' => 'UPDATE timeline_items SET ' . implode(', ', $sets) . ' WHERE id = ?', 'args' => $args];
    }

    printf("%d. %-13s %s%s\n",
        $rank + 1,
        $step['years'][0] ?? $row['year_fr'],
        $row['title_fr'],
        $step['years'] !== null ? "   (année : {$row['year_fr']} -> {$step['years'][0]})" : ''
    );
}

printf("\n%d requête(s) à exécuter.\n", count($updates));
if (!$apply) { exit("Simulation. Relancer avec --apply.\n"); }

$pdo->beginTransaction();
try {
    foreach ($updates as $u) { $pdo->prepare($u['sql'])->execute($u['args']); }
    $pdo->commit();
    echo "Appliqué.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    exit('Échec, rien modifié : ' . $e->getMessage() . "\n");
}
