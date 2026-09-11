<?php
/**
 * SDS — Compteur de visiteurs publique
 * Utilise la connexion PDO partagée via getDB()
 */
header('Content-Type: application/json');
require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/session_bootstrap.php';

try {
    $pdo = getDB();

    // Hash de l'IP pour la vie privée (on ne stocke jamais l'IP en clair).
    // sds_client_ip() et non REMOTE_ADDR : derrière le proxy Vercel ce dernier
    // valait 127.0.0.1 pour tous, et le compteur n'enregistrait qu'un visiteur
    // par jour — « 9 visiteurs » au total pour 157 sessions réelles.
    $ipHash = hash('sha256', sds_client_ip() . 'sds_salt_2025');
    $today = date('Y-m-d');

    // Enregistrer la visite (IGNORE si déjà visité aujourd'hui grâce à la clé
    // unique). Le pays, fourni par l'edge Vercel, n'était jamais renseigné :
    // l'admin affichait « Inconnu » pour tout le monde.
    $stmt = $pdo->prepare("INSERT IGNORE INTO visitors (ip_hash, country, visited_at) VALUES (:hash, :country, :today)");
    $stmt->execute([':hash' => $ipHash, ':country' => sds_client_country(), ':today' => $today]);

    // Enregistrer la vue de page
    sds_session_start();
    $session_id = session_id();

    // Ces trois valeurs viennent du navigateur : n'importe qui peut appeler
    // cette adresse avec ce qu'il veut. On ne garde que des valeurs plausibles,
    // de la taille des colonnes (un « device » hors liste faisait échouer
    // l'insertion, l'ENUM le refusant).
    $page = (string) ($_GET['page'] ?? '');
    // Le site n'envoie que le dernier segment de l'adresse : jamais de « / ».
    if ($page === '' || $page === 'index.php' || str_contains($page, '..')
        || !preg_match('#^[\w\-.]{1,100}$#', $page)) {
        $page = 'home';
    }
    $ref = trim((string) ($_GET['ref'] ?? ''));
    if ($ref !== '' && (strlen($ref) > 500 || !preg_match('#^https?://#i', $ref)
                        || !filter_var($ref, FILTER_VALIDATE_URL))) {
        $ref = '';
    }
    $device = in_array($_GET['device'] ?? '', ['desktop', 'mobile', 'tablet'], true) ? $_GET['device'] : 'desktop';

    // Limite de fréquence : sans elle, une boucle suffisait à gonfler les
    // statistiques à volonté. Au-delà, la vue n'est pas comptée, mais la
    // réponse reste normale (c'est du suivi, pas une fonction du site).
    $maxViews = 30;
    $pdo->exec("DELETE FROM api_rate_limits WHERE endpoint = 'visit' AND window_start < (NOW() - INTERVAL 60 SECOND)");
    $rl = $pdo->prepare("SELECT requests_count FROM api_rate_limits WHERE ip_hash = ? AND endpoint = 'visit'");
    $rl->execute([$ipHash]);
    $count = $rl->fetchColumn();
    if ($count === false) {
        $pdo->prepare("INSERT INTO api_rate_limits (ip_hash, endpoint, requests_count) VALUES (?, 'visit', 1)")->execute([$ipHash]);
    } else {
        $pdo->prepare("UPDATE api_rate_limits SET requests_count = requests_count + 1 WHERE ip_hash = ? AND endpoint = 'visit'")->execute([$ipHash]);
    }

    if ($count === false || (int) $count < $maxViews) {
        // Le pays de la vue n'était jamais renseigné, bien que la colonne existe.
        $stmtView = $pdo->prepare("INSERT INTO page_views (session_id, page, referrer, device, country) VALUES (:sess, :page, :ref, :device, :country)");
        $stmtView->execute([':sess' => $session_id, ':page' => $page, ':ref' => $ref, ':device' => $device,
                            ':country' => sds_client_country()]);
    }

    // Compter les statistiques
    $total = $pdo->query("SELECT COUNT(*) FROM visitors")->fetchColumn();
    $todayCount = $pdo->prepare("SELECT COUNT(*) FROM visitors WHERE visited_at = :today");
    $todayCount->execute([':today' => $today]);
    $todayVisitors = $todayCount->fetchColumn();

    // Visiteurs cette semaine
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $weekStmt = $pdo->prepare("SELECT COUNT(*) FROM visitors WHERE visited_at >= :week");
    $weekStmt->execute([':week' => $weekStart]);
    $weekVisitors = $weekStmt->fetchColumn();

    echo json_encode([
        'total'   => (int)$total,
        'today'   => (int)$todayVisitors,
        'week'    => (int)$weekVisitors,
        'status'  => 'ok'
    ]);

} catch (PDOException $e) {
    error_log("SDS Visitors Error: " . $e->getMessage());
    echo json_encode([
        'total'  => 0,
        'today'  => 0,
        'week'   => 0,
        'status' => 'error'
    ]);
}
?>
