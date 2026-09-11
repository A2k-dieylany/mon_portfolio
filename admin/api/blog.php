<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_auth();

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    if ($method === 'GET' && $action === 'list') {
        $stmt = $pdo->query("SELECT * FROM blog_posts ORDER BY sort_order ASC, publish_date DESC");
        json_response(['posts' => $stmt->fetchAll()]);
    } 
    elseif ($method === 'POST' && $action === 'save') {
        $id = $_POST['id'] ?? '';
        $emoji = $_POST['emoji'] ?? '📝';
        $category_fr = $_POST['category_fr'] ?? '';
        $category_en = $_POST['category_en'] ?? '';
        $category_ar = $_POST['category_ar'] ?? '';
        $title_fr = $_POST['title_fr'] ?? '';
        $title_en = $_POST['title_en'] ?? '';
        $title_ar = $_POST['title_ar'] ?? '';
        $excerpt_fr = $_POST['excerpt_fr'] ?? '';
        $excerpt_en = $_POST['excerpt_en'] ?? '';
        $excerpt_ar = $_POST['excerpt_ar'] ?? '';
        $external_url = clean_url($_POST['external_url'] ?? '');
        if ($external_url === '') {
            json_response(['error' => 'Le lien vers la publication est invalide (il doit commencer par https://).'], 400);
        }
        // Les anciens articles renvoyaient tous vers le profil LinkedIn : le
        // visiteur cliquait « Voir sur LinkedIn » et ne trouvait pas l'article.
        // Un lien LinkedIn doit donc désigner une publication précise.
        $host = strtolower((string) parse_url($external_url, PHP_URL_HOST));
        $path = (string) parse_url($external_url, PHP_URL_PATH);
        if (preg_match('/(^|\.)linkedin\.com$/', $host)
            && !preg_match('#^/(posts|feed/update|pulse)/.+#', $path)) {
            json_response(['error' => "Ce lien mène à ton profil LinkedIn, pas à un post. Sur LinkedIn, ouvre le post, "
                . "clique sur « … » puis « Copier le lien vers le post », et colle-le ici."], 400);
        }
        $read_time = $_POST['read_time'] ?? '';
        // Un champ date vide arrive en '' : « ?? » ne le remplaçait pas et
        // MySQL refusait la valeur, faisant échouer l'enregistrement.
        $publish_date = trim($_POST['publish_date'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $publish_date)) {
            $publish_date = date('Y-m-d');
        }
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $is_visible = isset($_POST['is_visible']) ? 1 : 0;

        if ($id) {
            $stmt = $pdo->prepare("UPDATE blog_posts SET emoji=?, category_fr=?, category_en=?, category_ar=?, title_fr=?, title_en=?, title_ar=?, excerpt_fr=?, excerpt_en=?, excerpt_ar=?, external_url=?, read_time=?, publish_date=?, sort_order=?, is_visible=? WHERE id=?");
            $stmt->execute([$emoji, $category_fr, $category_en, $category_ar, $title_fr, $title_en, $title_ar, $excerpt_fr, $excerpt_en, $excerpt_ar, $external_url, $read_time, $publish_date, $sort_order, $is_visible, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO blog_posts (emoji, category_fr, category_en, category_ar, title_fr, title_en, title_ar, excerpt_fr, excerpt_en, excerpt_ar, external_url, read_time, publish_date, sort_order, is_visible) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$emoji, $category_fr, $category_en, $category_ar, $title_fr, $title_en, $title_ar, $excerpt_fr, $excerpt_en, $excerpt_ar, $external_url, $read_time, $publish_date, $sort_order, $is_visible]);
        }
        json_response(['success' => true]);
    } 
    elseif ($method === 'POST' && $action === 'delete') {
        $id = $_POST['id'] ?? '';
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM blog_posts WHERE id=?");
            $stmt->execute([$id]);
            json_response(['success' => true]);
        }
        json_response(['error' => 'Identifiant manquant.'], 400);
    }
    json_response(['error' => 'Action inconnue.'], 400);
} catch (Exception $e) {
    // Le message SQL brut était renvoyé au navigateur.
    error_log('Admin blog.php : ' . $e->getMessage());
    json_response(['error' => 'Erreur serveur.'], 500);
}
