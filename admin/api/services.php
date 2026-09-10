<?php
/**
 * SDS Admin API — Services CRUD
 *
 * Gère la carte du service (accueil) et sa page détaillée /services/<slug> :
 * accroche, tarif, délai et contenu HTML, en trois langues. Ces colonnes
 * existaient en base mais n'étaient pas éditables : changer un prix
 * demandait une requête SQL.
 */
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

require_auth();
security_headers();

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDB();

// Champs texte simples (carte + page détaillée). Le contenu détaillé, lui,
// est du HTML filtré par clean_html().
const SERVICE_TEXT_FIELDS = [
    'icon', 'title_fr', 'title_en', 'title_ar', 'desc_fr', 'desc_en', 'desc_ar', 'tags',
    'headline_fr', 'headline_en', 'headline_ar',
    'price_from', 'price_from_en', 'price_from_ar',
    'delay_text', 'delay_text_en', 'delay_text_ar',
];
const SERVICE_HTML_FIELDS = ['detail_fr', 'detail_en', 'detail_ar'];

/**
 * Normalise et contrôle l'adresse de la page (/services/<slug>).
 * Vide = pas de page détaillée. Refuse un slug déjà pris par un autre service.
 */
function service_slug(PDO $pdo, ?string $raw, ?int $selfId): ?string
{
    $slug = slugify(trim((string) $raw));
    if ($slug === '') {
        return null;
    }
    $stmt = $pdo->prepare('SELECT id FROM services WHERE slug = ? AND id <> ? LIMIT 1');
    $stmt->execute([$slug, $selfId ?? 0]);
    if ($stmt->fetchColumn()) {
        json_response(['error' => "L'adresse /services/$slug est déjà utilisée par un autre service."], 409);
    }
    return $slug;
}

// Colonnes déclarées NOT NULL : vides, elles reçoivent '' (le site retombe
// alors sur la version française), jamais NULL, que MySQL refuserait.
const SERVICE_NOT_NULL = ['icon', 'title_fr', 'title_en', 'title_ar', 'desc_fr', 'desc_en', 'desc_ar'];

// Longueurs maximales des colonnes : dépasser donnerait une erreur SQL
// incompréhensible au lieu d'un message clair.
const SERVICE_MAX_LENGTH = [
    'icon' => 10, 'title_fr' => 200, 'title_en' => 200, 'title_ar' => 200, 'tags' => 200,
    'headline_fr' => 200, 'headline_en' => 200, 'headline_ar' => 200,
    'price_from' => 60, 'price_from_en' => 60, 'price_from_ar' => 60,
    'delay_text' => 120, 'delay_text_en' => 120, 'delay_text_ar' => 120,
];

/** Valeur d'un champ pour l'écriture : NULL si vide pour les colonnes optionnelles. */
function service_value(string $field, $value): ?string
{
    $clean = in_array($field, SERVICE_HTML_FIELDS, true) ? clean_html($value) : clean_text($value);
    $max = SERVICE_MAX_LENGTH[$field] ?? null;
    if ($max !== null && mb_strlen($clean) > $max) {
        json_response(['error' => "Le champ '$field' dépasse $max caractères."], 422);
    }
    if ($clean === '') {
        return in_array($field, SERVICE_NOT_NULL, true) ? '' : null;
    }
    return $clean;
}

try {
    // ===== GET LIST =====
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT * FROM services ORDER BY sort_order ASC, id ASC");
        json_response(['services' => $stmt->fetchAll()]);
    }

    // ===== POST (Create) =====
    if ($method === 'POST') {
        $data = get_json_body();
        require_fields($data, ['title_fr', 'desc_fr', 'icon']);

        $cols = ['slug', 'sort_order', 'is_visible'];
        $vals = [
            service_slug($pdo, $data['slug'] ?? '', null),
            (int) ($data['sort_order'] ?? 0),
            isset($data['is_visible']) ? (int) $data['is_visible'] : 1,
        ];
        foreach (array_merge(SERVICE_TEXT_FIELDS, SERVICE_HTML_FIELDS) as $f) {
            if (!array_key_exists($f, $data)) {
                continue;
            }
            $cols[] = $f;
            $vals[] = service_value($f, $data[$f]);
        }
        // Une colonne NOT NULL absente de la requête ferait échouer l'insertion.
        foreach (SERVICE_NOT_NULL as $f) {
            if (!in_array($f, $cols, true)) {
                $cols[] = $f;
                $vals[] = '';
            }
        }
        // Les champs obligatoires ne doivent pas rester vides.
        foreach (['icon', 'title_fr', 'desc_fr'] as $f) {
            $i = array_search($f, $cols, true);
            if ($i === false || $vals[$i] === null || $vals[$i] === '') {
                json_response(['error' => "Le champ '$f' est requis."], 400);
            }
        }

        $sql = 'INSERT INTO services (' . implode(', ', $cols) . ') VALUES ('
             . implode(', ', array_fill(0, count($cols), '?')) . ')';
        $pdo->prepare($sql)->execute($vals);

        json_response(['success' => true, 'message' => 'Service ajouté.', 'id' => (int) $pdo->lastInsertId()]);
    }

    // ===== PUT (Update) =====
    if ($method === 'PUT') {
        $data = get_json_body();
        require_fields($data, ['id']);
        $id = (int) $data['id'];

        $sets = [];
        $params = [];

        if (array_key_exists('slug', $data)) {
            $sets[] = 'slug = ?';
            $params[] = service_slug($pdo, $data['slug'], $id);
        }
        foreach (['sort_order', 'is_visible'] as $f) {
            if (array_key_exists($f, $data)) {
                $sets[] = "$f = ?";
                $params[] = (int) $data[$f];
            }
        }
        foreach (array_merge(SERVICE_TEXT_FIELDS, SERVICE_HTML_FIELDS) as $f) {
            if (!array_key_exists($f, $data)) {
                continue;
            }
            $value = service_value($f, $data[$f]);
            if (($value === null || $value === '') && in_array($f, ['icon', 'title_fr', 'desc_fr'], true)) {
                json_response(['error' => "Le champ '$f' ne peut pas être vide."], 400);
            }
            $sets[] = "$f = ?";
            $params[] = $value;
        }

        if (empty($sets)) json_response(['error' => 'Rien à modifier.'], 400);

        $params[] = $id;
        $stmt = $pdo->prepare("UPDATE services SET " . implode(', ', $sets) . " WHERE id = ?");
        $stmt->execute($params);

        json_response(['success' => true, 'message' => 'Service mis à jour.']);
    }

    // ===== DELETE =====
    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) json_response(['error' => 'ID requis.'], 400);

        $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
        $stmt->execute([$id]);

        json_response(['success' => true, 'message' => 'Service supprimé.']);
    }

    json_response(['error' => 'Méthode non supportée.'], 405);

} catch (PDOException $e) {
    error_log("SDS Admin Services Error: " . $e->getMessage());
    json_response(['error' => 'Erreur serveur.'], 500);
}
